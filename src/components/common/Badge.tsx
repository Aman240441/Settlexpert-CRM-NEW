import React from 'react';

interface BadgeProps {
  status: string;
  variant?: 'solid' | 'subtle';
  size?: 'sm' | 'md';
}

export const Badge: React.FC<BadgeProps> = ({ status, variant = 'subtle', size = 'sm' }) => {
  const s = (status || '').toLowerCase();

  let colorClasses = 'bg-slate-50 text-slate-700 border-slate-200';

  if (['new'].includes(s)) {
    // New: Light blue background, Blue text
    colorClasses = variant === 'solid'
      ? 'bg-blue-600 text-white border-blue-600'
      : 'bg-blue-50 text-blue-700 border-blue-200/80';
  } else if (['contacted'].includes(s)) {
    // Contacted: Light cyan background, Info blue text
    colorClasses = variant === 'solid'
      ? 'bg-sky-600 text-white border-sky-600'
      : 'bg-sky-50 text-sky-700 border-sky-200/80';
  } else if (['qualified'].includes(s)) {
    // Qualified: Light green background, Green text
    colorClasses = variant === 'solid'
      ? 'bg-emerald-600 text-white border-emerald-600'
      : 'bg-emerald-50 text-emerald-700 border-emerald-200/80';
  } else if (['proposal'].includes(s)) {
    // Proposal: Light amber background, Amber text
    colorClasses = variant === 'solid'
      ? 'bg-amber-600 text-white border-amber-600'
      : 'bg-amber-50 text-amber-700 border-amber-200/80';
  } else if (['won', 'active', 'completed', 'verified', 'paid', 'converted'].includes(s)) {
    // Won / Active / Completed: Light green background, Green text
    colorClasses = variant === 'solid'
      ? 'bg-emerald-600 text-white border-emerald-600'
      : 'bg-emerald-50 text-emerald-700 border-emerald-200/80';
  } else if (['lost', 'rejected', 'inactive', 'cancelled', 'failed'].includes(s)) {
    // Lost / Rejected: Light red background, Red text
    colorClasses = variant === 'solid'
      ? 'bg-red-600 text-white border-red-600'
      : 'bg-red-50 text-red-700 border-red-200/80';
  } else if (['pending', 'pending_verification', 'negotiating', 'in_progress', 'urgent', 'high'].includes(s)) {
    // PENDING: Light amber background, Amber text
    colorClasses = variant === 'solid'
      ? 'bg-amber-600 text-white border-amber-600'
      : 'bg-amber-50 text-amber-700 border-amber-200/80';
  }

  const sizeClasses = size === 'sm' ? 'px-2.5 py-0.5 text-xs font-medium' : 'px-3 py-1 text-sm font-semibold';

  const label = status ? status.replace(/_/g, ' ').toUpperCase() : 'N/A';

  return (
    <span className={`inline-flex items-center rounded-full border ${sizeClasses} ${colorClasses} tracking-wide`}>
      <span className="w-1.5 h-1.5 rounded-full mr-1.5 bg-current opacity-75"></span>
      {label}
    </span>
  );
};
