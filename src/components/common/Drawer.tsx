import React, { useEffect } from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';
import { lockBodyScroll, unlockBodyScroll } from '../../utils/scrollLock';

interface DrawerProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  subtitle?: string;
  children: React.ReactNode;
  width?: 'md' | 'lg' | 'xl' | '2xl';
}

export const Drawer: React.FC<DrawerProps> = ({
  isOpen,
  onClose,
  title,
  subtitle,
  children,
  width = 'xl',
}) => {
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    if (isOpen) {
      lockBodyScroll();
      window.addEventListener('keydown', handleKeyDown);
    }
    return () => {
      if (isOpen) {
        unlockBodyScroll();
        window.removeEventListener('keydown', handleKeyDown);
      }
    };
  }, [isOpen, onClose]);

  if (!isOpen || typeof document === 'undefined') return null;

  const widthMap = {
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
  };

  const drawerElement = (
    <div
      className="fixed inset-0 z-[1000] overflow-hidden w-screen h-screen"
      style={{ top: 0, left: 0, right: 0, bottom: 0 }}
      role="dialog"
      aria-modal="true"
    >
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/70 backdrop-blur-xs transition-opacity z-[900]"
        onClick={onClose}
        aria-hidden="true"
      />

      <div className="fixed inset-y-0 right-0 flex max-w-full pl-6 sm:pl-10 z-[1000]">
        <div
          className={`w-screen ${widthMap[width]} transform bg-white border-l border-[#E2E8F0] shadow-2xl flex flex-col h-full`}
          onClick={(e) => e.stopPropagation()}
        >
          {/* Drawer Header */}
          <div className="sticky top-0 z-10 flex items-center justify-between border-b border-[#E2E8F0] px-6 py-5 bg-[#F8FAFC] shrink-0">
            <div>
              <h2 className="text-xl font-bold text-[#1E293B] tracking-tight">{title}</h2>
              {subtitle && <p className="text-xs text-[#64748B] mt-1">{subtitle}</p>}
            </div>
            <button
              type="button"
              onClick={onClose}
              aria-label="Close drawer"
              className="rounded-lg p-2 text-[#64748B] hover:bg-[#E2E8F0]/60 hover:text-[#1E293B] transition-colors cursor-pointer"
            >
              <X className="h-5 w-5" />
            </button>
          </div>

          {/* Drawer Content */}
          <div className="flex-1 overflow-y-auto p-6 space-y-6 text-[#334155]">{children}</div>
        </div>
      </div>
    </div>
  );

  return createPortal(drawerElement, document.body);
};
