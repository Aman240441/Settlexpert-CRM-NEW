import React, { useState, useRef, useEffect } from 'react';
import { Search, LogOut, ChevronDown, Maximize2, Calendar, Mail, Briefcase, Shield } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { NotificationBell } from './NotificationBell';

interface HeaderProps {
  currentSection: string;
  onSearchChange?: (val: string) => void;
  onNavigate: (section: string) => void;
  extraActions?: React.ReactNode;
}

export const Header: React.FC<HeaderProps> = ({ currentSection, onSearchChange, onNavigate, extraActions }) => {
  const { user, logout } = useAuth();
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const [showNotifications, setShowNotifications] = useState(false);
  const [searchVal, setSearchVal] = useState('');

  const profileRef = useRef<HTMLDivElement>(null);
  const notifRef = useRef<HTMLDivElement>(null);

  // Click outside listener to close dropdowns automatically
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (profileRef.current && !profileRef.current.contains(e.target as Node)) {
        setShowProfileMenu(false);
      }
      if (notifRef.current && !notifRef.current.contains(e.target as Node)) {
        setShowNotifications(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchVal(e.target.value);
    if (onSearchChange) onSearchChange(e.target.value);
  };

  const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {});
    } else {
      document.exitFullscreen().catch(() => {});
    }
  };

  const notifications = [
    { id: 1, title: 'Payment Pending Verification', desc: 'REC-2026-062: ₹25,000 for client Pradeep Chawla', time: '10m ago', unread: true },
    { id: 2, title: 'New Agreement Generated', desc: 'AGR-2067 pending client confirmation', time: '1h ago', unread: true },
    { id: 3, title: 'Advocate Assigned', desc: 'Adv. Amit Verma linked to case CL-8848', time: '3h ago', unread: false },
  ];

  return (
    <header className="sticky top-0 z-10 flex h-16 w-full items-center justify-between border-b border-[#E2E8F0] bg-white px-6 font-sans">
      {/* Left: Breadcrumb / Active Section */}
      <div className="flex items-center space-x-3">
        <div className="flex items-center space-x-2">
          <div className="h-2 w-2 rounded-full bg-[#22C55E] animate-pulse" />
          <span className="text-xs font-bold uppercase tracking-wider text-[#2563EB]">Welcome Back!</span>
        </div>
        <span className="text-slate-300 font-bold">/</span>
        <h1 className="text-sm font-bold text-[#1E293B] tracking-tight capitalize">
          {currentSection.replace(/-/g, ' ')}
        </h1>
      </div>

      {/* Middle: Quick Search */}
      <div className="hidden md:flex items-center w-80 relative">
        <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-[#94A3B8]" />
        <input
          type="text"
          placeholder="Global search managers, employees, cases..."
          value={searchVal}
          onChange={handleSearch}
          className="w-full pl-9 pr-4 py-1.5 bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl text-xs text-[#334155] placeholder-[#94A3B8] focus:outline-none focus:border-[#3B82F6] focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all shadow-xs"
        />
      </div>

      {/* Right: Actions & Admin Profile */}
      <div className="flex items-center space-x-3">
        {extraActions}

        {/* Fullscreen Button */}
        <button
          type="button"
          onClick={toggleFullscreen}
          className="p-1.5 rounded-lg hover:bg-[#F8FAFC] text-slate-600 transition-colors hidden sm:block cursor-pointer"
          title="Toggle Fullscreen"
          aria-label="Toggle Fullscreen"
        >
          <Maximize2 className="h-4 w-4" />
        </button>

        {/* Real-time Payment Notifications Bell */}
        <NotificationBell onViewClient={(clientId) => onNavigate('clients')} />

        {/* Profile Dropdown */}
        <div className="relative" ref={profileRef}>
          <button
            type="button"
            onClick={() => {
              setShowProfileMenu(!showProfileMenu);
              setShowNotifications(false);
            }}
            className="flex items-center space-x-2.5 p-1 pl-1.5 rounded-xl hover:bg-[#F8FAFC] transition-colors cursor-pointer"
            aria-label="User profile menu"
            aria-expanded={showProfileMenu}
          >
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-[#3B82F6] text-xs font-black text-white shadow-xs overflow-hidden border border-blue-600/30">
              {user?.profile_image ? (
                <img src={user.profile_image} alt={user.name} className="h-full w-full object-cover" />
              ) : (
                <span>{user?.name ? user.name.charAt(0).toUpperCase() : 'A'}</span>
              )}
            </div>
            <div className="hidden text-left lg:block">
              <span className="text-xs font-bold text-[#1E293B] block leading-tight truncate max-w-[130px]">{user?.name}</span>
              <span className="text-[10px] text-[#2563EB] font-bold block leading-tight">Super Administrator</span>
            </div>
            <ChevronDown className="h-3.5 w-3.5 text-slate-500" />
          </button>

          {showProfileMenu && (
            <div className="absolute right-0 mt-2 w-72 rounded-2xl bg-white border border-[#E2E8F0] shadow-xl z-[100] animate-fade-in text-[#334155] text-xs overflow-hidden text-left">
              {/* Header Banner */}
              <div className="p-4 bg-[#1E293B] text-white">
                <div className="flex items-center space-x-3">
                  <div className="h-12 w-12 rounded-xl bg-white/10 border border-white/20 text-white flex items-center justify-center font-black text-lg shadow-sm overflow-hidden shrink-0">
                    {user?.profile_image ? (
                      <img src={user.profile_image} alt={user.name} className="h-full w-full object-cover" />
                    ) : (
                      <span>{user?.name ? user.name.charAt(0).toUpperCase() : 'A'}</span>
                    )}
                  </div>
                  <div className="min-w-0 flex-1">
                    <h4 className="text-sm font-black text-white truncate leading-tight">{user?.name}</h4>
                    <p className="text-[11px] text-blue-300 font-bold truncate mt-0.5">Super Administrator</p>
                    <span className="inline-block mt-1 px-2 py-0.5 rounded-md bg-blue-600/30 border border-blue-400/40 text-[9px] font-bold text-blue-200 uppercase tracking-wider">
                      Master Control
                    </span>
                  </div>
                </div>
              </div>

              {/* Profile Details List */}
              <div className="p-3 space-y-2 text-[11px] text-[#334155] bg-[#F8FAFC] border-b border-[#E2E8F0]">
                <div className="flex items-center justify-between">
                  <span className="text-[#64748B] flex items-center gap-1.5 font-medium">
                    <Calendar className="h-3.5 w-3.5 text-[#3B82F6] shrink-0" />
                    <span>Joining Date</span>
                  </span>
                  <span className="font-bold text-[#1E293B] font-mono">
                    {user?.joining_date ? new Date(user.joining_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Permanent'}
                  </span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-[#64748B] flex items-center gap-1.5 font-medium">
                    <Mail className="h-3.5 w-3.5 text-[#3B82F6] shrink-0" />
                    <span>Email</span>
                  </span>
                  <span className="font-medium text-[#1E293B] truncate max-w-[150px]" title={user?.email}>
                    {user?.email}
                  </span>
                </div>
              </div>

              <div className="p-1.5 space-y-0.5">
                <button
                  type="button"
                  onClick={() => {
                    setShowProfileMenu(false);
                    onNavigate('settings');
                  }}
                  className="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-50 font-semibold text-slate-700 transition-colors cursor-pointer flex items-center space-x-2"
                >
                  <Briefcase className="h-3.5 w-3.5 text-slate-500" />
                  <span>System Settings</span>
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowProfileMenu(false);
                    onNavigate('audit');
                  }}
                  className="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-50 font-semibold text-slate-700 transition-colors cursor-pointer flex items-center space-x-2"
                >
                  <Shield className="h-3.5 w-3.5 text-slate-500" />
                  <span>Global Audit Logs</span>
                </button>
                <button
                  type="button"
                  onClick={logout}
                  className="w-full text-left px-3 py-2 rounded-xl hover:bg-rose-50 text-rose-600 font-bold transition-colors flex items-center space-x-2 cursor-pointer"
                >
                  <LogOut className="h-3.5 w-3.5" />
                  <span>Sign Out</span>
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};
