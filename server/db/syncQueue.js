const db = require('./database');
const { syncRecord } = require('./supabaseClient');

/**
 * Maps a SQLite record to the Supabase payload schema based on table rules.
 * Derived from sync_to_supabase.js mapping logic.
 */
function mapPayload(tableName, record) {
  if (!record) return null;
  
  if (tableName === 'leads') {
    return {
      id: Math.floor(Number(record.id)),
      lead_id: record.lead_number || `LD-${record.id}`,
      name: record.name || 'Unnamed',
      phone: record.phone || '',
      email: record.email || '',
      city: record.city || '',
      outstanding_amount: String(Math.floor(Number(record.total_debt || record.loan_amount || 0))),
      monthly_income: String(Math.floor(Number(record.monthly_income || 0))),
      lead_status: record.status || 'New',
      created_at: record.created_at || new Date().toISOString()
    };
  } else if (tableName === 'clients') {
    return {
      id: Math.floor(Number(record.id)),
      client_id: record.client_number || `CL-${record.id}`,
      name: record.name || 'Unnamed',
      phone: record.phone || '',
      email: record.email || '',
      city: record.city || '',
      service_fee: Math.floor(Number(record.sx_fee || 0)),
      pending_amount: Math.floor(Number(record.pending_amount || 0)),
      case_status: record.case_status || 'Active',
      created_at: record.created_at || new Date().toISOString()
    };
  } else if (tableName === 'agreements') {
    return {
      id: Math.floor(Number(record.id)),
      agreement_id: record.agreement_number || `AGR-${record.id}`,
      client_id_ref: record.client_id ? Math.floor(Number(record.client_id)) : null,
      client_name: record.name || '',
      phone: record.phone || '',
      email: record.email || '',
      loan_amount: Math.floor(Number(record.total_fee || 0)),
      resolution_duration: record.resolution_duration || '',
      status: record.status || 'Active',
      created_at: record.created_at || new Date().toISOString()
    };
  } else if (tableName === 'follow_ups') {
    // Basic mapping, sync_to_supabase.js didn't sync follow ups explicitly but we need it
    return {
      id: record.id,
      lead_id: record.lead_id,
      user_id: record.user_id,
      user_name: record.user_name,
      call_status: record.call_status,
      final_status: record.final_status,
      remark: record.remark,
      created_at: record.created_at
    };
  }
  
  // Default raw
  return record;
}

/**
 * Attempts to sync a record to Supabase immediately.
 * If it fails, adds it to the local sync_queue for background retry.
 */
async function syncRecordWithFallback(tableName, recordId) {
  try {
    // 1. Fetch latest record state from local DB
    const record = db.prepare(`SELECT * FROM ${tableName} WHERE id = ?`).get(recordId);
    
    if (!record) {
      // Record was physically deleted locally. If soft deletes are used, this won't happen.
      // But if it does, we can't sync it.
      return false;
    }

    const payload = mapPayload(tableName, record);
    if (!payload) return false;

    // 2. Try to sync to Supabase
    try {
      await syncRecord(tableName, payload);
      
      // If successful and it was in the queue, remove it
      db.prepare(`DELETE FROM sync_queue WHERE table_name = ? AND record_id = ?`).run(tableName, recordId);
      return true;
    } catch (err) {
      // 3. Fallback: Add to sync queue if it fails
      enqueueSync(tableName, recordId, err.message);
      return false;
    }
  } catch (err) {
    console.error(`[SyncQueue] Critical error syncing ${tableName}:${recordId} -`, err.message);
    enqueueSync(tableName, recordId, err.message);
    return false;
  }
}

/**
 * Adds an item to the local SQLite sync queue.
 */
function enqueueSync(tableName, recordId, errorMessage = '') {
  try {
    db.prepare(`
      INSERT INTO sync_queue (table_name, record_id, error_message, updated_at) 
      VALUES (?, ?, ?, CURRENT_TIMESTAMP)
      ON CONFLICT(table_name, record_id) DO UPDATE SET 
        status = 'pending',
        error_message = excluded.error_message,
        updated_at = CURRENT_TIMESTAMP
    `).run(tableName, String(recordId), String(errorMessage));
    console.log(`[SyncQueue] Enqueued ${tableName}:${recordId} for background retry.`);
  } catch (e) {
    console.error('[SyncQueue] Failed to enqueue sync item:', e.message);
  }
}

/**
 * Processes the sync queue in the background.
 */
async function processSyncQueue() {
  try {
    const pendingItems = db.prepare(`SELECT * FROM sync_queue WHERE status = 'pending' LIMIT 50`).all();
    if (pendingItems.length === 0) return;

    console.log(`[SyncQueue] Processing ${pendingItems.length} pending items...`);

    for (const item of pendingItems) {
      const success = await syncRecordWithFallback(item.table_name, item.record_id);
      
      if (!success) {
        db.prepare(`
          UPDATE sync_queue 
          SET retry_count = retry_count + 1, updated_at = CURRENT_TIMESTAMP 
          WHERE id = ?
        `).run(item.id);
      }
    }
  } catch (err) {
    console.error('[SyncQueue] Process queue error:', err.message);
  }
}

module.exports = {
  syncRecordWithFallback,
  enqueueSync,
  processSyncQueue
};
