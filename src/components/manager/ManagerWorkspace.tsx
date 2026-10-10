import React, { useState, useEffect } from 'react';
import {
  Gauge,
  Users,
  UserCheck,
  FileSignature,
  CreditCard,
  Scale,
  Clock,
  CheckSquare,
  Activity,
  LogOut,
  ArrowLeftRight,
  Shield,
  Briefcase,
  Layers,
  Menu,
  Maximize2,
  ChevronDown,
  ChevronRight,
  User as UserIcon,
  FileSpreadsheet,
  Calendar,
  Mail,
  Phone,
  Building2
} from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { api } from '../../services/api';
import { ManagerDashboardView } from './ManagerDashboardView';
import { ManagerTeamView } from './ManagerTeamView';
import { ManagerLeadsView } from './ManagerLeadsView';
import { ManagerClientsView } from './ManagerClientsView';
import { ManagerAgreementsView } from './ManagerAgreementsView';
import { ManagerFollowUpsView } from './ManagerFollowUpsView';
import { ManagerTasksView } from './ManagerTasksView';
import { ManagerPaymentsVerificationView } from './ManagerPaymentsVerificationView';
import { ManagerPaymentManagementView } from './ManagerPaymentManagementView';
import { ManagerAdvocatesView } from './ManagerAdvocatesView';
import { ManagerActivityView } from './ManagerActivityView';
import { NotificationBell } from '../common/NotificationBell';
import { LeadImportDistributionView } from '../leads/LeadImportDistributionView';
import { ClientOnboardingMainView } from '../crm/ClientOnboardingMainView';

interface ManagerWorkspaceProps {
  onSwitchToAdmin?: () => void;
}

export const ManagerWorkspace: React.FC<ManagerWorkspaceProps> = ({ onSwitchToAdmin }) => {
  const { user, logout } = useAuth();
  const [managerContext, setManagerContext] = useState<any>(null);
  const [activeTab, setActiveTab] = useState<string>('dashboard');
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [teamOpen, setTeamOpen] = useState(true);
  const [crmOpen, setCrmOpen] = useState(true);
  const [financeOpen, setFinanceOpen] = useState(true);
  const [legalOpen, setLegalOpen] = useState(true);
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchContext = async () => {
      try {
        setLoading(true);
        const res = await api.getManagerContext();
        setManagerContext(res);
      } catch (err) {
        console.error('Failed to load manager context', err);
      } finally {
        setLoading(false);
      }
    };
    fetchContext();
  }, []);

  const managerType = (managerContext?.managerType || 'LEGAL').toUpperCase();
  const isFinance = managerType === 'FIN' || managerType === 'FINANCE' || managerType === 'COLLECTION';
  const isLegal = managerType === 'LEGAL';
  const isHR = managerType === 'HR';

  const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => { });
    } else {
      document.exitFullscreen().catch(() => { });
    }
  };

  const profileRef = React.useRef<HTMLDivElement>(null);

  React.useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (profileRef.current && !profileRef.current.contains(e.target as Node)) {
        setShowProfileMenu(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  return (
    <div className="min-h-screen bg-[#F8FAFC] text-[#334155] flex font-sans antialiased">
      {/* Sidebar */}
      <aside
        className={`bg-[#1E293B] border-r border-[#334155] transition-all duration-300 flex flex-col shrink-0 z-[500] ${sidebarOpen ? 'w-64' : 'w-20'
          }`}
      >
        {/* Top Logo */}
        <div className="h-20 flex items-center px-5 border-b border-[#334155]">
          {sidebarOpen ? (
            <div className="bg-white px-3 py-1.5 rounded-xl shadow-xs inline-flex items-center">
              <img
                src="/logo.png"
                alt="SettleXpert"
                className="h-8 max-h-8 w-auto max-w-[190px] object-contain"
              />
            </div>
          ) : (
            <div className="bg-white p-1.5 rounded-xl shadow-xs mx-auto">
              <img
                src="/logo.png"
                alt="SettleXpert"
                className="h-7 w-7 object-contain"
              />
            </div>
          )}
        </div>

        {/* Sidebar Menu Items */}
        <nav className="flex-1 p-3 space-y-1.5 overflow-y-auto text-xs font-semibold">
          {/* Dashboard */}
          <button
            onClick={() => setActiveTab('dashboard')}
            className={`w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all ${activeTab === 'dashboard'
              ? 'bg-[#3B82F6] text-white shadow-sm'
              : 'text-slate-300 hover:bg-[#334155] hover:text-white'
              }`}
          >
            <Gauge className="h-4 w-4 shrink-0" />
            {sidebarOpen && <span className="font-bold">Dashboard</span>}
          </button>

          {/* Team Management */}
          <div>
            <button
              onClick={() => {
                setTeamOpen(!teamOpen);
                setActiveTab('team');
              }}
              className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all ${activeTab === 'team'
                ? 'bg-[#334155] text-white'
                : 'text-slate-300 hover:bg-[#334155] hover:text-white'
                }`}
            >
              <div className="flex items-center space-x-3">
                <Users className="h-4 w-4 shrink-0" />
                {sidebarOpen && <span>My Team</span>}
              </div>
              {sidebarOpen && (
                <ChevronDown
                  className={`h-3.5 w-3.5 transition-transform ${teamOpen ? 'rotate-0' : '-rotate-90'}`}
                />
              )}
            </button>
            {sidebarOpen && teamOpen && (
              <div className="pl-8 pr-1 py-1 space-y-1 border-l border-[#334155] ml-4">
                <button
                  onClick={() => setActiveTab('team')}
                  className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'team'
                    ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                    : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                    }`}
                >
                  Employees & Quotas
                </button>
              </div>
            )}
          </div>

          {/* CRM Management (Leads, Clients, Agreements) */}
          {!isHR && (
            <div>
              <button
                onClick={() => {
                  setCrmOpen(!crmOpen);
                  if (activeTab !== 'leads' && activeTab !== 'clients' && activeTab !== 'agreements') {
                    setActiveTab('clients');
                  }
                }}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all ${activeTab === 'leads' || activeTab === 'clients' || activeTab === 'agreements' || activeTab === 'follow-ups' || activeTab === 'onboarding-form'
                  ? 'bg-[#334155] text-white'
                  : 'text-slate-300 hover:bg-[#334155] hover:text-white'
                  }`}
              >
                <div className="flex items-center space-x-3">
                  <UserCheck className="h-4 w-4 shrink-0" />
                  {sidebarOpen && <span>CRM Control</span>}
                </div>
                {sidebarOpen && (
                  <ChevronDown
                    className={`h-3.5 w-3.5 transition-transform ${crmOpen ? 'rotate-0' : '-rotate-90'}`}
                  />
                )}
              </button>
              {sidebarOpen && crmOpen && (
                <div className="pl-8 pr-1 py-1 space-y-1 border-l border-[#334155] ml-4">
                  <button
                    onClick={() => setActiveTab('clients')}
                    className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'clients'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                      }`}
                  >
                    Clients & Cases
                  </button>
                  <button
                    onClick={() => setActiveTab('leads')}
                    className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'leads'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                      }`}
                  >
                    Leads Pipeline
                  </button>
                  <button
                    onClick={() => setActiveTab('lead-import-distribution')}
                    className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all flex items-center justify-between ${activeTab === 'lead-import-distribution'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                      }`}
                  >
                    <span>Import & Distribute</span>
                    <span className="text-[9px] bg-blue-500/20 text-blue-200 font-bold px-1.5 py-0.2 rounded">Excel</span>
                  </button>
                  <button
                    onClick={() => setActiveTab('agreements')}
                    className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'agreements'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                      }`}
                  >
                    Agreements
                  </button>
                  <button
                    onClick={() => setActiveTab('follow-ups')}
                    className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'follow-ups'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                      }`}
                  >
                    Follow-ups Monitor
                  </button>
                </div>
              )}
            </div>
          )}

          {/* Legal Advocate Control */}
          {(isLegal || user?.role === 'admin') && (
            <div>
              <button
                onClick={() => {
                  setLegalOpen(!legalOpen);
                  setActiveTab('advocates');
                }}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all ${activeTab === 'advocates'
                  ? 'bg-[#334155] text-white'
                  : 'text-slate-300 hover:bg-[#334155] hover:text-white'
                  }`}
              >
                <div className="flex items-center space-x-3">
                  <Scale className="h-4 w-4 shrink-0" />
                  {sidebarOpen && <span>Legal Counsel</span>}
                </div>
                {sidebarOpen && (
                  <ChevronDown
                    className={`h-3.5 w-3.5 transition-transform ${legalOpen ? 'rotate-0' : '-rotate-90'}`}
                  />
                )}
              </button>
              {sidebarOpen && legalOpen && (
                <div className="pl-8 pr-1 py-1 space-y-1 border-l border-[#334155] ml-4">
                  <button
                    onClick={() => setActiveTab('advocates')}
                    className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'advocates'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                      }`}
                  >
                    Empanelled Advocates
                  </button>
                </div>
              )}
            </div>
          )}

          {/* Finance Verification */}
          {(isFinance || user?.role === 'admin') && (
            <div>
              <button
                onClick={() => {
                  setFinanceOpen(!financeOpen);
                  if (activeTab !== 'payments' && activeTab !== 'verification') {
                    setActiveTab('payments');
                  }
                }}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all ${activeTab === 'payments' || activeTab === 'verification'
                  ? 'bg-[#334155] text-white'
                  : 'text-slate-300 hover:bg-[#334155] hover:text-white'
                  }`}
              >
                <div className="flex items-center space-x-3">
                  <CreditCard className="h-4 w-4 shrink-0" />
                  {sidebarOpen && <span>Payment & Finance</span>}
                </div>
                {sidebarOpen && (
                  <ChevronDown
                    className={`h-3.5 w-3.5 transition-transform ${financeOpen ? 'rotate-0' : '-rotate-90'}`}
                  />
                )}
              </button>
              {sidebarOpen && financeOpen && (
                <div className="pl-8 pr-1 py-1 space-y-1 border-l border-[#334155] ml-4">
                  <button
                    onClick={() => setActiveTab('payments')}
                    className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'payments'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                      }`}
                  >
                    Payment Management
                  </button>
                  <button
                    onClick={() => setActiveTab('verification')}
                    className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'verification'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                      }`}
                  >
                    Payment Verification
                  </button>
                </div>
              )}
            </div>
          )}

          {/* Tasks & Activity */}
          <button
            onClick={() => setActiveTab('tasks')}
            className={`w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all ${activeTab === 'tasks'
              ? 'bg-[#3B82F6] text-white shadow-sm'
              : 'text-slate-300 hover:bg-[#334155] hover:text-white'
              }`}
          >
            <CheckSquare className="h-4 w-4 shrink-0" />
            {sidebarOpen && <span>Tasks & Notes</span>}
          </button>

          <button
            onClick={() => setActiveTab('activity')}
            className={`w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all ${activeTab === 'activity'
              ? 'bg-[#3B82F6] text-white shadow-sm'
              : 'text-slate-300 hover:bg-[#334155] hover:text-white'
              }`}
          >
            <Activity className="h-4 w-4 shrink-0" />
            {sidebarOpen && <span>Activity Stream</span>}
          </button>
        </nav>

        {/* Sidebar Footer */}
        {sidebarOpen && (
          <div className="p-3 border-t border-[#334155] text-center">
            <span className="text-[10px] text-slate-400 font-medium">Settl Expert v1.0 • {managerType} Manager</span>
          </div>
        )}
      </aside>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden bg-[#F8FAFC]">
        {/* Top Header Bar */}
        <header className="h-14 bg-white border-b border-[#E2E8F0] px-4 flex items-center justify-between shrink-0">
          {/* Left: Hamburger & Welcome Back */}
          <div className="flex items-center space-x-3">
            <button
              onClick={() => setSidebarOpen(!sidebarOpen)}
              className="p-1.5 rounded-lg hover:bg-[#F8FAFC] text-slate-600 transition-colors"
            >
              <Menu className="h-5 w-5" />
            </button>
            <div className="flex items-center space-x-2">
              <span className="text-sm font-bold text-[#1E293B] tracking-tight">
                Welcome Back!
              </span>
              <span className="hidden sm:inline-block px-2 py-0.5 rounded bg-blue-50 border border-blue-200 text-[#2563EB] text-[10px] font-bold uppercase">
                {managerType} Manager Panel
              </span>
            </div>
          </div>

          {/* Right: Notifications, Fullscreen & User Profile */}
          <div className="flex items-center space-x-3">
            {/* Notification Bell */}
            <NotificationBell onViewClient={(clientId) => { setActiveTab('clients'); }} />

            {/* Admin Switcher */}
            {user?.role === 'admin' && onSwitchToAdmin && (
              <button
                onClick={onSwitchToAdmin}
                className="px-3 py-1.5 rounded-xl bg-white hover:bg-[#F8FAFC] border border-[#E2E8F0] text-[#334155] hover:text-[#2563EB] text-xs font-semibold transition-all flex items-center space-x-1.5 shadow-xs cursor-pointer"
              >
                <ArrowLeftRight className="h-3.5 w-3.5 text-[#3B82F6]" />
                <span className="hidden sm:inline">Admin Mode</span>
              </button>
            )}

            {/* Fullscreen Button */}
            <button
              onClick={toggleFullscreen}
              className="p-1.5 rounded-lg hover:bg-[#F8FAFC] text-slate-600 transition-colors hidden sm:block cursor-pointer"
              title="Toggle Fullscreen"
            >
              <Maximize2 className="h-4 w-4" />
            </button>

            {/* Profile Menu Dropdown */}
            <div className="relative" ref={profileRef}>
              <button
                type="button"
                onClick={() => setShowProfileMenu(!showProfileMenu)}
                className="flex items-center space-x-2.5 p-1 pl-1.5 rounded-xl hover:bg-[#F8FAFC] transition-colors cursor-pointer"
                aria-label="Manager profile menu"
                aria-expanded={showProfileMenu}
              >
                <div className="h-9 w-9 rounded-xl bg-[#3B82F6] text-white flex items-center justify-center font-black text-xs shadow-xs overflow-hidden border border-blue-600/30">
                  {user?.profile_image ? (
                    <img src={user.profile_image} alt={user.name} className="h-full w-full object-cover" />
                  ) : (
                    <span>{user?.name ? user.name.charAt(0).toUpperCase() : 'M'}</span>
                  )}
                </div>
                <div className="hidden md:flex flex-col text-left">
                  <span className="text-xs font-bold text-[#1E293B] leading-tight truncate max-w-[130px]">
                    {user?.name || 'Manager'}
                  </span>
                  <span className="text-[10px] text-[#2563EB] font-semibold leading-tight truncate max-w-[130px]">
                    {user?.designation || user?.manager_type_name || `${managerType} Manager`}
                  </span>
                </div>
                <ChevronDown className="h-3.5 w-3.5 text-slate-500" />
              </button>

              {/* Profile Dropdown Popup */}
              {showProfileMenu && (
                <div className="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-[#E2E8F0] py-0 z-[100] animate-in fade-in zoom-in-95 duration-100 overflow-hidden text-left">
                  {/* Header Banner with Profile Picture & Info */}
                  <div className="p-4 bg-[#1E293B] text-white relative">
                    <div className="flex items-center space-x-3.5">
                      <div className="h-12 w-12 rounded-xl bg-white/10 border border-white/20 text-white flex items-center justify-center font-black text-lg shadow-sm overflow-hidden shrink-0">
                        {user?.profile_image ? (
                          <img src={user.profile_image} alt={user.name} className="h-full w-full object-cover" />
                        ) : (
                          <span>{user?.name ? user.name.charAt(0).toUpperCase() : 'M'}</span>
                        )}
                      </div>
                      <div className="min-w-0 flex-1">
                        <h4 className="text-sm font-black text-white truncate leading-tight">{user?.name}</h4>
                        <p className="text-[11px] text-blue-200 font-bold truncate mt-0.5">
                          {user?.designation || user?.manager_type_name || `${managerType} Manager`}
                        </p>
                        <div className="flex items-center gap-1.5 mt-1.5 flex-wrap">
                          <span className="px-2 py-0.5 rounded-md bg-blue-600/30 border border-blue-400/40 text-[10px] font-bold text-blue-200 tracking-wide font-mono">
                            {user?.emp_or_mgr_id || 'ID: —'}
                          </span>
                          <span className="px-2 py-0.5 rounded-md bg-white/20 text-[10px] font-bold text-white uppercase tracking-wider">
                            {managerType} Manager
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Profile Details List */}
                  <div className="p-3.5 space-y-2.5 text-xs text-[#334155] bg-[#F8FAFC] border-b border-[#E2E8F0]">
                    {/* Designation */}
                    <div className="flex items-center justify-between text-[11px]">
                      <span className="text-[#64748B] flex items-center gap-1.5 font-medium">
                        <Briefcase className="h-3.5 w-3.5 text-[#3B82F6] shrink-0" />
                        <span>Designation</span>
                      </span>
                      <span className="font-bold text-[#1E293B] truncate max-w-[160px]">
                        {user?.designation || user?.manager_type_name || `${managerType} Manager`}
                      </span>
                    </div>

                    {/* Joining Date */}
                    <div className="flex items-center justify-between text-[11px]">
                      <span className="text-slate-500 flex items-center gap-1.5 font-medium">
                        <Calendar className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Joining Date</span>
                      </span>
                      <span className="font-bold text-slate-800 font-mono">
                        {user?.joining_date ? new Date(user.joining_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '04 Sep 2026'}
                      </span>
                    </div>

                    {/* Department */}
                    <div className="flex items-center justify-between text-[11px]">
                      <span className="text-slate-500 flex items-center gap-1.5 font-medium">
                        <Building2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Department</span>
                      </span>
                      <span className="font-bold text-slate-800 truncate max-w-[160px]">
                        {user?.department_name || `${managerType} Department`}
                      </span>
                    </div>

                    {/* Email */}
                    <div className="flex items-center justify-between text-[11px]">
                      <span className="text-slate-500 flex items-center gap-1.5 font-medium">
                        <Mail className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Email</span>
                      </span>
                      <span className="font-medium text-slate-800 truncate max-w-[160px]" title={user?.email}>
                        {user?.email}
                      </span>
                    </div>

                    {/* Phone */}
                    {user?.phone && (
                      <div className="flex items-center justify-between text-[11px]">
                        <span className="text-slate-500 flex items-center gap-1.5 font-medium">
                          <Phone className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                          <span>Phone</span>
                        </span>
                        <span className="font-medium text-slate-800 font-mono">
                          {user.phone}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Actions */}
                  <div className="p-2 space-y-1">
                    {onSwitchToAdmin && (
                      <button
                        onClick={() => {
                          setShowProfileMenu(false);
                          onSwitchToAdmin();
                        }}
                        className="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-xl flex items-center space-x-2 transition-colors cursor-pointer"
                      >
                        <ArrowLeftRight className="h-3.5 w-3.5 text-indigo-600" />
                        <span>Switch to Master Admin</span>
                      </button>
                    )}

                    <button
                      onClick={() => {
                        setShowProfileMenu(false);
                        logout();
                      }}
                      className="w-full text-left px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-xl flex items-center space-x-2 transition-colors cursor-pointer"
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

        {/* Main Content Area */}
        <main className="flex-1 overflow-y-auto p-4 sm:p-6 max-w-7xl w-full mx-auto">
          {activeTab === 'dashboard' && (
            <ManagerDashboardView onNavigateTab={(tab) => setActiveTab(tab)} />
          )}
          {activeTab === 'team' && <ManagerTeamView />}
          {activeTab === 'leads' && (
            <ManagerLeadsView onNavigateToImport={() => setActiveTab('lead-import-distribution')} />
          )}
          {activeTab === 'lead-import-distribution' && (
            <LeadImportDistributionView isManager={true} onNavigateToLeads={() => setActiveTab('leads')} />
          )}
          {activeTab === 'clients' && <ManagerClientsView managerType={managerType} />}
          {activeTab === 'agreements' && <ManagerAgreementsView />}
          {activeTab === 'onboarding-form' && <ClientOnboardingMainView userRole="manager" />}
          {activeTab === 'payments' && <ManagerPaymentManagementView />}
          {activeTab === 'verification' && <ManagerPaymentsVerificationView />}
          {activeTab === 'advocates' && <ManagerAdvocatesView />}
          {activeTab === 'follow-ups' && <ManagerFollowUpsView />}
          {activeTab === 'tasks' && <ManagerTasksView />}
          {activeTab === 'activity' && <ManagerActivityView />}
        </main>
      </div>
    </div>
  );
};
