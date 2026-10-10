import React from 'react';
import { LucideIcon } from 'lucide-react';

interface StatCardProps {
  title: string;
  value: string | number;
  icon: LucideIcon;
  trend?: string;
  trendPositive?: boolean;
  subtitle?: string;
  color?: 'blue' | 'emerald' | 'amber' | 'purple' | 'rose' | 'indigo';
  onClick?: () => void;
}

export const StatCard: React.FC<StatCardProps> = ({
  title,
  value,
  icon: Icon,
  trend,
  trendPositive = true,
  subtitle,
  color = 'blue',
  onClick,
}) => {
  const iconBgMap = {
    blue: 'bg-blue-50 text-[#3B82F6] border border-blue-100',
    emerald: 'bg-emerald-50 text-[#22C55E] border border-emerald-100',
    amber: 'bg-amber-50 text-[#F59E0B] border border-amber-100',
    purple: 'bg-blue-50 text-[#3B82F6] border border-blue-100',
    rose: 'bg-red-50 text-[#EF4444] border border-red-100',
    indigo: 'bg-blue-50 text-[#3B82F6] border border-blue-100',
  };

  return (
    <div
      onClick={onClick}
      className={`group relative overflow-hidden rounded-xl bg-white p-5 border border-[#E2E8F0] shadow-xs transition-all duration-200 hover:border-slate-300 hover:shadow-sm ${
        onClick ? 'cursor-pointer' : ''
      }`}
    >
      <div className="flex items-center justify-between">
        <div className="space-y-1">
          <p className="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">{title}</p>
          <h4 className="text-2xl font-black tracking-tight text-[#1E293B]">{value}</h4>
        </div>
        <div className={`p-3 rounded-xl ${iconBgMap[color]} transition-transform duration-200 group-hover:scale-105 shadow-2xs`}>
          <Icon className="h-5 w-5" />
        </div>
      </div>

      {(trend || subtitle) && (
        <div className="mt-3.5 flex items-center justify-between text-xs pt-3 border-t border-[#E2E8F0]">
          {subtitle && <span className="text-[#64748B] font-medium text-[11px]">{subtitle}</span>}
          {trend && (
            <span
              className={`font-bold text-[11px] flex items-center gap-1 ${
                trendPositive ? 'text-[#22C55E]' : 'text-[#EF4444]'
              }`}
            >
              {trendPositive ? '↑' : '↓'} {trend}
            </span>
          )}
        </div>
      )}
    </div>
  );
};
