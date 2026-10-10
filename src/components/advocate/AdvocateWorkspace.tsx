import React, { useState } from 'react';
import {
  Scale,
  Gauge,
  Briefcase,
  UserCheck,
  FileSignature,
  CheckCircle2,
  Menu,
  Maximize2,
  ChevronDown,
  ChevronRight,
  LogOut,
  ArrowLeftRight,
  User as UserIcon,
  ShieldAlert,
  Activity,
  Award
} from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { AdvocateDashboardView } from './AdvocateDashboardView';
import { AdvocateCasesView } from './AdvocateCasesView';
import { AdvocateTasksView } from './AdvocateTasksView';
import { AdvocateLegalNoticesView } from './AdvocateLegalNoticesView';
import { AdvocateDemandNoticesView } from './AdvocateDemandNoticesView';

interface AdvocateWorkspaceProps {
  onSwitchToAdmin?: () => void;
}

export const AdvocateWorkspace: React.FC<AdvocateWorkspaceProps> = ({ onSwitchToAdmin }) => {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState<'dashboard' | 'cases' | 'legal_notices' | 'demand_notices' | 'tasks'>('dashboard');
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [casesOpen, setCasesOpen] = useState(true);
  const [tasksOpen, setTasksOpen] = useState(true);

  const [caseFilter, setCaseFilter] = useState<string>('active');
  const [showProfileMenu, setShowProfileMenu] = useState(false);
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

  const handleNavigateToCases = (filter?: string) => {
    setCaseFilter(filter || 'active');
    setActiveTab('cases');
  };

  const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => { });
    } else {
      document.exitFullscreen().catch(() => { });
    }
  };

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

          {/* My Assigned Cases */}
          <div>
            <button
              onClick={() => {
                setCasesOpen(!casesOpen);
                setActiveTab('cases');
              }}
              className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all ${activeTab === 'cases'
                  ? 'bg-[#334155] text-white'
                  : 'text-slate-300 hover:bg-[#334155] hover:text-white'
                }`}
            >
              <div className="flex items-center space-x-3">
                <Briefcase className="h-4 w-4 shrink-0" />
                {sidebarOpen && <span>My Assigned Cases</span>}
              </div>
              {sidebarOpen && (
                <ChevronDown
                  className={`h-3.5 w-3.5 transition-transform ${casesOpen ? 'rotate-0' : '-rotate-90'}`}
                />
              )}
            </button>
            {sidebarOpen && casesOpen && (
              <div className="pl-8 pr-1 py-1 space-y-1 border-l border-[#334155] ml-4">
                <button
                  onClick={() => {
                    setCaseFilter('active');
                    setActiveTab('cases');
                  }}
                  className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'cases' && caseFilter === 'active'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                    }`}
                >
                  Active Cases
                </button>
                <button
                  onClick={() => {
                    setCaseFilter('closed');
                    setActiveTab('cases');
                  }}
                  className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'cases' && caseFilter === 'closed'
                      ? 'bg-[#3B82F6] text-white font-bold shadow-xs'
                      : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                    }`}
                >
                  Closed / Dropped
                </button>
              </div>
            )}
          </div>

          {/* Legal Notices & Anti-Harassment Defense */}
          <button
            onClick={() => setActiveTab('legal_notices')}
            className={`w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all ${
              activeTab === 'legal_notices'
                ? 'bg-[#3B82F6] text-white shadow-sm'
                : 'text-slate-300 hover:bg-[#334155] hover:text-white'
            }`}
          >
            <FileSignature className="h-4 w-4 shrink-0" />
            {sidebarOpen && <span className="font-bold">Legal Notices</span>}
          </button>

          {/* Demand Notices & OTS Counter Offers */}
          <button
            onClick={() => setActiveTab('demand_notices')}
            className={`w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all ${
              activeTab === 'demand_notices'
                ? 'bg-[#3B82F6] text-white shadow-sm'
                : 'text-slate-300 hover:bg-[#334155] hover:text-white'
            }`}
          >
            <ShieldAlert className="h-4 w-4 shrink-0" />
            {sidebarOpen && <span className="font-bold">Demand Notices</span>}
          </button>

          {/* Legal Actions & Notice Responses */}
          <div>
            <button
              onClick={() => {
                setTasksOpen(!tasksOpen);
                setActiveTab('tasks');
              }}
              className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all ${activeTab === 'tasks'
                  ? 'bg-[#334155] text-white'
                  : 'text-slate-300 hover:bg-[#334155] hover:text-white'
                }`}
            >
              <div className="flex items-center space-x-3">
                <CheckCircle2 className="h-4 w-4 shrink-0" />
                {sidebarOpen && <span>Tasks & Deadlines</span>}
              </div>
              {sidebarOpen && (
                <ChevronDown
                  className={`h-3.5 w-3.5 transition-transform ${tasksOpen ? 'rotate-0' : '-rotate-90'}`}
                />
              )}
            </button>
            {sidebarOpen && tasksOpen && (
              <div className="pl-8 pr-1 py-1 space-y-1 border-l border-[#334155] ml-4">
                <button
                  onClick={() => setActiveTab('tasks')}
                  className={`w-full text-left py-1.5 px-3 rounded-lg text-[11px] font-medium transition-all ${activeTab === 'tasks' ? 'bg-[#3B82F6] text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-[#334155]'
                    }`}
                >
                  Action Items
                </button>
              </div>
            )}
          </div>
        </nav>

        {/* Sidebar Footer */}
        {sidebarOpen && (
          <div className="p-3 border-t border-[#334155] text-center">
            <span className="text-[10px] text-slate-400 font-medium">Settl Expert v1.0 • Legal Counsel</span>
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
            <span className="text-sm font-bold text-[#1E293B] tracking-tight">
              Welcome Back!
            </span>
          </div>

          {/* Right: Fullscreen & User Profile */}
          <div className="flex items-center space-x-3">
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
                className="flex items-center space-x-2 p-1 pl-2 rounded-xl hover:bg-[#F8FAFC] transition-colors cursor-pointer"
                aria-label="Advocate profile menu"
                aria-expanded={showProfileMenu}
              >
                <div className="h-8 w-8 rounded-full bg-[#3B82F6] text-white flex items-center justify-center font-bold text-xs shadow-xs overflow-hidden shrink-0">
                  {user?.profile_image ? (
                    <img src={user.profile_image} alt={user.name} className="h-full w-full object-cover" />
                  ) : (
                    user?.name ? user.name.charAt(0) : 'A'
                  )}
                </div>
                <span className="text-xs font-bold text-[#1E293B] hidden md:block">
                  {user?.name || 'Advocate'}
                </span>
                <ChevronDown className="h-3.5 w-3.5 text-slate-500" />
              </button>

              {/* Profile Dropdown Popup */}
              {showProfileMenu && (
                <div className="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-[#E2E8F0] py-2 z-[100] animate-in fade-in zoom-in-95 duration-100">
                  <div className="px-4 py-2 border-b border-[#E2E8F0]">
                    <p className="text-xs font-bold text-[#1E293B] truncate">{user?.name}</p>
                    <p className="text-[11px] text-[#64748B] truncate">{user?.email}</p>
                    <span className="inline-block mt-1 px-2 py-0.5 rounded bg-blue-50 text-[#2563EB] text-[10px] font-bold">
                      Empanelled Advocate Counsel
                    </span>
                  </div>

                  {/* Switch to Admin Mode if Admin */}
                  {onSwitchToAdmin && (
                    <button
                      onClick={() => {
                        setShowProfileMenu(false);
                        onSwitchToAdmin();
                      }}
                      className="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-gray-50 flex items-center space-x-2 border-b border-gray-100"
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
                    className="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 flex items-center space-x-2"
                  >
                    <LogOut className="h-3.5 w-3.5" />
                    <span>Sign Out</span>
                  </button>
                </div>
              )}
            </div>
          </div>
        </header>

        {/* Main Content Area */}
        <main className="flex-1 overflow-y-auto p-4 sm:p-6">
          {activeTab === 'dashboard' && (
            <AdvocateDashboardView
              onNavigateToCases={handleNavigateToCases}
              onOpenCase={(c) => {
                setCaseFilter('active');
                setActiveTab('cases');
              }}
            />
          )}

          {activeTab === 'cases' && (
            <AdvocateCasesView initialCaseStatusFilter={caseFilter} />
          )}

          {activeTab === 'legal_notices' && (
            <AdvocateLegalNoticesView />
          )}

          {activeTab === 'demand_notices' && (
            <AdvocateDemandNoticesView onDraftReplyNotice={() => setActiveTab('legal_notices')} />
          )}

          {activeTab === 'tasks' && (
            <AdvocateTasksView />
          )}
        </main>
      </div>
    </div>
  );
};
