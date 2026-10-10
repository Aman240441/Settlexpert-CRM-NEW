import React, { useState } from 'react';
import { Mail, Lock, Eye, EyeOff, CheckCircle2, AlertCircle } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';

export const LoginView: React.FC = () => {
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await login({ email, password });
    } catch (err: any) {
      setError(err.message || 'Invalid email/employee ID or password');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen w-full flex flex-col justify-center items-center p-4 relative overflow-hidden bg-[#F8FAFC] font-sans selection:bg-blue-100 selection:text-blue-900">
      {/* Subtle soft backdrop accents */}
      <div className="absolute inset-0 pointer-events-none overflow-hidden">
        <div className="absolute -top-[15%] -left-[10%] w-[60vw] h-[60vw] rounded-full bg-blue-50/60 blur-3xl" />
        <div className="absolute -bottom-[15%] -right-[10%] w-[60vw] h-[60vw] rounded-full bg-slate-100/80 blur-3xl" />
      </div>

      {/* Main Login Card */}
      <div className="w-full max-w-[420px] bg-white rounded-2xl p-8 sm:p-9 shadow-sm border border-[#E2E8F0] relative z-10 space-y-6">
        {/* Brand Header */}
        <div className="text-center flex flex-col items-center">
          <div className="flex items-center justify-center mb-3">
            <img
              src="/logo.png"
              alt="SettleXpert"
              className="h-11 sm:h-12 w-auto max-w-[220px] object-contain"
            />
          </div>
          <h1 className="text-lg sm:text-xl font-bold text-[#1E293B] tracking-tight">
            CRM Portal
          </h1>
          <p className="text-xs text-[#64748B] font-medium mt-0.5">
            Sign in to access your workspace
          </p>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center space-x-2 font-semibold">
            <AlertCircle className="h-4 w-4 shrink-0 text-red-600" />
            <span>{error}</span>
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-xs font-semibold text-[#334155] block mb-1.5">
              Email Address / Employee ID
            </label>
            <div className="relative">
              <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-[#94A3B8]" />
              <input
                type="text"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Enter your ID"
                autoComplete="off"
                className="w-full pl-10 pr-4 py-2.5 bg-white border border-[#E2E8F0] rounded-xl text-xs text-[#334155] placeholder-[#94A3B8] focus:outline-none focus:border-[#3B82F6] focus:ring-2 focus:ring-blue-100 transition-all font-medium"
              />
            </div>
          </div>

          <div>
            <label className="text-xs font-semibold text-[#334155] block mb-1.5">
              Password
            </label>
            <div className="relative">
              <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-[#94A3B8]" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Enter your password"
                autoComplete="new-password"
                className="w-full pl-10 pr-10 py-2.5 bg-white border border-[#E2E8F0] rounded-xl text-xs text-[#334155] placeholder-[#94A3B8] focus:outline-none focus:border-[#3B82F6] focus:ring-2 focus:ring-blue-100 transition-all font-medium"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
              >
                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              </button>
            </div>
          </div>

          <div className="flex items-center justify-between pt-0.5">
            <label className="flex items-center space-x-2 cursor-pointer">
              <input
                type="checkbox"
                checked={rememberMe}
                onChange={(e) => setRememberMe(e.target.checked)}
                className="h-4 w-4 rounded border-[#E2E8F0] text-[#3B82F6] focus:ring-[#3B82F6] cursor-pointer"
              />
              <span className="text-xs text-[#64748B] font-medium">Remember me</span>
            </label>

            <button
              type="button"
              onClick={() => alert('Please contact Super Administrator (settlexperts@gmail.com) to reset your password.')}
              className="text-xs font-bold text-[#3B82F6] hover:text-[#2563EB] hover:underline cursor-pointer"
            >
              Forgot password?
            </button>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-2.5 rounded-xl bg-[#3B82F6] hover:bg-[#2563EB] active:bg-blue-700 text-white text-xs font-bold transition-all shadow-xs flex items-center justify-center space-x-2 disabled:opacity-50 cursor-pointer mt-2"
          >
            <CheckCircle2 className="h-4 w-4 text-white" />
            <span>{loading ? 'Signing In...' : 'Sign In to Portal'}</span>
          </button>
        </form>
      </div>
    </div>
  );
};
