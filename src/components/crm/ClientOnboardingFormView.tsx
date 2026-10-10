import React, { useState, useEffect } from 'react';
import {
  Printer,
  Copy,
  Check,
  ExternalLink,
  Edit2,
  Save,
  X,
  User,
  Wallet,
  AlertCircle,
  ShieldCheck,
  Loader2
} from 'lucide-react';
import { api } from '../../services/api';

interface ClientOnboardingFormViewProps {
  client: any;
  lenders?: any[];
  agreements?: any[];
  payments?: any[];
  monthlyPaymentData?: any;
  onOpenAgreement?: (client: any) => void;
}

export const ClientOnboardingFormView: React.FC<ClientOnboardingFormViewProps> = ({
  client: initialClient,
  lenders = [],
  agreements = [],
  payments = [],
  monthlyPaymentData,
  onOpenAgreement,
}) => {
  const [client, setClient] = useState<any>(initialClient);
  const [copiedKey, setCopiedKey] = useState<string | null>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [formData, setFormData] = useState<any>({});

  useEffect(() => {
    setClient(initialClient);
    setFormData(initialClient);
  }, [initialClient]);

  if (!client) {
    return (
      <div className="p-8 text-center text-slate-400 bg-white rounded-xl border border-gray-200">
        No client details loaded.
      </div>
    );
  }

  const handleCopy = (text: string, key: string) => {
    if (!text || text === '—') return;
    navigator.clipboard.writeText(text);
    setCopiedKey(key);
    setTimeout(() => setCopiedKey(null), 2000);
  };

  const handlePrint = () => {
    window.print();
  };

  const handleChange = (field: string, value: any) => {
    setFormData((prev: any) => ({ ...prev, [field]: value }));
  };

  const handleSave = async () => {
    try {
      setIsSaving(true);
      await api.updateCRMClient(client.id, formData);
      setClient({ ...client, ...formData });
      setIsEditing(false);
    } catch (error) {
      console.error('Failed to update client', error);
      alert('Failed to save changes. Please try again.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleCancel = () => {
    setFormData(client);
    setIsEditing(false);
  };

  const totalDebt = formData.total_debt || client.total_debt || 450000;
  const monthlyIncome = formData.monthly_income || client.monthly_income || 35000;
  const previousEmi = formData.monthly_emi || formData.total_emi || client.monthly_emi || client.total_emi || 20000;
  const emiRatio = formData.emi_ratio || client.emi_ratio || Math.round((previousEmi / (monthlyIncome || 1)) * 100);

  const sections = [
    {
      id: 'personal',
      title: 'Personal Details',
      icon: <User className="h-4 w-4 text-blue-600" />,
      fields: [
        { label: 'Client Name', key: 'name', type: 'text' },
        { label: 'Client Email', key: 'email', type: 'email' },
        { label: 'Client Phone', key: 'phone', type: 'tel' },
        { label: 'Occupation', key: 'occupation', type: 'text' },
        { label: 'Marital Status', key: 'marital_status', type: 'text' },
        { label: 'Living With', key: 'living_with', type: 'text' },
        { label: 'Accommodation', key: 'accommodation', type: 'text' },
        { label: 'Spouse Phone', key: 'spouse_phone', type: 'tel' },
      ],
    },
    {
      id: 'financial',
      title: 'Financial Details',
      icon: <Wallet className="h-4 w-4 text-emerald-600" />,
      fields: [
        { label: 'Total Loan Amount', key: 'total_debt', type: 'number', default: totalDebt },
        { label: 'SX Services Fee', key: 'sx_services_fee', type: 'number', default: client.sx_services_fee || client.sx_fee || 8000 },
        { label: 'Number of Lenders', key: 'lenders_count', type: 'number', default: lenders.length > 0 ? lenders.length : 4 },
        { label: 'Monthly Income', key: 'monthly_income', type: 'number', default: monthlyIncome },
        { label: 'Salary Bank', key: 'salary_bank', type: 'text' },
        { label: 'Salary Date', key: 'salary_date', type: 'number' },
        { label: 'Auto Debit Lenders', key: 'auto_debit_lenders', type: 'number' },
        { label: 'Blank Cheque Lenders', key: 'blank_cheque_lenders', type: 'number' },
        { label: 'Spouse Monthly Income', key: 'spouse_monthly_income', type: 'number' },
        { label: 'Parents Monthly Income', key: 'parents_monthly_income', type: 'number' },
        { label: 'Other Sources Income', key: 'other_sources_income', type: 'number' },
        { label: 'Family Monthly Income', key: 'family_monthly_income', type: 'number' },
        { label: 'Family Monthly Expenses', key: 'family_monthly_expenses', type: 'number' },
        { label: 'Previous Monthly EMI', key: 'monthly_emi', type: 'number', default: previousEmi },
        { label: 'EMI to Income Ratio (%)', key: 'emi_ratio', type: 'number', default: emiRatio },
        { label: 'How were EMIs managed?', key: 'how_emis_managed', type: 'text' },
      ],
    },
    {
      id: 'hardship',
      title: 'Hardship & Reasons',
      icon: <AlertCircle className="h-4 w-4 text-rose-600" />,
      fields: [
        { label: 'Reasons for taking loan', key: 'reasons_taking_loan', type: 'textarea' },
        { label: 'Reasons for not paying', key: 'reasons_not_paying', type: 'textarea' },
        { label: 'Source of settlement funds', key: 'source_settlement_funds', type: 'textarea' },
      ],
    },
    {
      id: 'legal',
      title: 'Legal & Compliance',
      icon: <ShieldCheck className="h-4 w-4 text-indigo-600" />,
      fields: [
        { label: 'Points explained in call', key: 'onboarding_points_explained', type: 'textarea' },
        { label: 'Truecaller Premium Subscribed?', key: 'truecaller_subscribed', type: 'select', options: ['Yes', 'No', 'Client has agreed to subscribe to Truecaller Premium'] },
        { label: 'CCTV Camera Agreed?', key: 'cctv_agreed', type: 'select', options: ['Yes', 'No', 'Client has agreed to install CCTV camera'] },
        { label: 'Spouse Aware?', key: 'spouse_aware', type: 'select', options: ['Yes', 'No'] },
        { label: 'Parents Aware?', key: 'parents_aware', type: 'select', options: ['Yes', 'No'] },
        { label: 'Likelihood of early drop', key: 'early_drop_likelihood', type: 'select', options: ['Low', 'Medium', 'High'] },
        { label: 'Advocate Name', key: 'advocate_name', type: 'text' },
        { label: 'Advocate Email', key: 'advocate_email', type: 'email' },
        { label: 'Additional Comments', key: 'comments', type: 'textarea' },
      ],
    },
  ];

  return (
    <div className="space-y-6 font-sans text-slate-800 pb-16 animate-fade-in min-h-0 h-auto">
      {/* Header Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
        <div>
          <h2 className="text-lg font-black text-slate-900 tracking-tight flex items-center space-x-2">
            <span>Onboarding Dossier</span>
            {isEditing && <span className="px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider">Edit Mode</span>}
          </h2>
          <p className="text-xs text-slate-500 mt-1">
            Complete details gathered during the client onboarding process.
          </p>
        </div>

        <div className="flex items-center space-x-2 shrink-0">
          {!isEditing ? (
            <>
              <button
                onClick={() => setIsEditing(true)}
                className="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold flex items-center space-x-1.5 shadow-sm transition-all cursor-pointer"
              >
                <Edit2 className="h-3.5 w-3.5" />
                <span>Edit Form</span>
              </button>
              <button
                onClick={handlePrint}
                className="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold flex items-center space-x-1.5 shadow-sm transition-all cursor-pointer"
                title="Print Official Onboarding Form"
              >
                <Printer className="h-3.5 w-3.5" />
                <span>Print</span>
              </button>
              {onOpenAgreement && (
                <button
                  onClick={() => onOpenAgreement(client)}
                  className="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold flex items-center space-x-1.5 shadow-sm transition-colors cursor-pointer"
                >
                  <ExternalLink className="h-3.5 w-3.5" />
                  <span>Agreement</span>
                </button>
              )}
            </>
          ) : (
            <>
              <button
                onClick={handleCancel}
                disabled={isSaving}
                className="px-3.5 py-2 bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-200 hover:text-rose-600 text-slate-600 rounded-xl text-xs font-bold flex items-center space-x-1.5 shadow-sm transition-all cursor-pointer disabled:opacity-50"
              >
                <X className="h-3.5 w-3.5" />
                <span>Cancel</span>
              </button>
              <button
                onClick={handleSave}
                disabled={isSaving}
                className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center space-x-1.5 shadow-sm transition-colors cursor-pointer disabled:opacity-70"
              >
                {isSaving ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Save className="h-3.5 w-3.5" />}
                <span>{isSaving ? 'Saving...' : 'Save Changes'}</span>
              </button>
            </>
          )}
        </div>
      </div>

      {/* Sections Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {sections.map((section) => (
          <div key={section.id} className={`bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col ${section.fields.some(f => f.type === 'textarea') ? 'lg:col-span-2' : ''}`}>
            <div className="bg-slate-50 border-b border-gray-100 px-5 py-3.5 flex items-center space-x-2.5">
              <div className={`p-1.5 rounded-lg bg-white shadow-xs border border-gray-100`}>
                {section.icon}
              </div>
              <h3 className="font-bold text-slate-800 text-sm">{section.title}</h3>
            </div>
            
            <div className={`p-5 grid gap-5 ${section.fields.some(f => f.type === 'textarea') ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1 sm:grid-cols-2'}`}>
              {section.fields.map((field) => {
                const val = formData[field.key] !== undefined ? formData[field.key] : (field.default !== undefined ? field.default : '');
                
                return (
                  <div key={field.key} className={`group ${field.type === 'textarea' ? 'col-span-1 md:col-span-2' : ''}`}>
                    <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                      {field.label}
                    </label>
                    
                    {!isEditing ? (
                      <div className="flex items-start justify-between p-2.5 -mx-2.5 rounded-lg hover:bg-slate-50 transition-colors">
                        <span className="text-sm font-medium text-slate-900 break-words">
                          {val || <span className="text-slate-300 font-normal italic">Not provided</span>}
                        </span>
                        {val && val !== '—' && (
                          <button
                            type="button"
                            onClick={() => handleCopy(String(val), field.key)}
                            className="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-blue-600 p-1 transition-all cursor-pointer shrink-0"
                            title="Copy"
                          >
                            {copiedKey === field.key ? (
                              <Check className="h-3.5 w-3.5 text-emerald-600" />
                            ) : (
                              <Copy className="h-3.5 w-3.5" />
                            )}
                          </button>
                        )}
                      </div>
                    ) : (
                      <div className="relative">
                        {field.type === 'textarea' ? (
                          <textarea
                            value={val}
                            onChange={(e) => handleChange(field.key, e.target.value)}
                            rows={3}
                            className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none shadow-xs"
                            placeholder={`Enter ${field.label.toLowerCase()}`}
                          />
                        ) : field.type === 'select' ? (
                          <select
                            value={val}
                            onChange={(e) => handleChange(field.key, e.target.value)}
                            className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-xs appearance-none"
                          >
                            <option value="">Select option...</option>
                            {field.options?.map(opt => (
                              <option key={opt} value={opt}>{opt}</option>
                            ))}
                          </select>
                        ) : (
                          <input
                            type={field.type}
                            value={val}
                            onChange={(e) => handleChange(field.key, field.type === 'number' ? Number(e.target.value) : e.target.value)}
                            className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-xs"
                            placeholder={`Enter ${field.label.toLowerCase()}`}
                          />
                        )}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};
