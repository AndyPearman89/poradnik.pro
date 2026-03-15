import React from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext.jsx';

const navItems = [
  { to: '/',             icon: '🏠', label: 'Dashboard',        roles: ['admin', 'tenant', 'vendor'] },
  { to: '/tenants',      icon: '🏢', label: 'Tenants',          roles: ['admin'] },
  { to: '/vendors',      icon: '🏪', label: 'Vendors',          roles: ['admin', 'tenant'] },
  { to: '/site-config',  icon: '⚙️',  label: 'Site Config',     roles: ['admin'] },
  { to: '/users',        icon: '👥', label: 'Users & Roles',    roles: ['admin'] },
  { to: '/articles',     icon: '📝', label: 'Articles',         roles: ['admin', 'tenant', 'vendor'] },
  { to: '/analytics',    icon: '📊', label: 'Analytics',        roles: ['admin', 'tenant', 'vendor'] },
];

export default function Sidebar() {
  const { user, isAdmin, isTenantOwner, isVendorUser, logout } = useAuth();
  const navigate = useNavigate();

  function userRole() {
    if (isAdmin) return 'admin';
    if (isTenantOwner) return 'tenant';
    if (isVendorUser) return 'vendor';
    return 'vendor';
  }

  const role = userRole();
  const visible = navItems.filter((item) => item.roles.includes(role));

  async function handleLogout() {
    await logout();
    navigate('/login');
  }

  return (
    <aside
      style={{
        width: 'var(--sidebar-width)',
        minHeight: '100vh',
        background: '#0f172a',
        color: '#cbd5e1',
        display: 'flex',
        flexDirection: 'column',
        flexShrink: 0,
        position: 'sticky',
        top: 0,
        height: '100vh',
        overflowY: 'auto',
      }}
    >
      {/* Logo */}
      <div
        style={{
          padding: '20px 24px 16px',
          borderBottom: '1px solid #1e293b',
        }}
      >
        <div style={{ fontSize: 18, fontWeight: 700, color: '#f8fafc' }}>
          🌿 Poradnik.pro
        </div>
        <div style={{ fontSize: 11, color: '#64748b', marginTop: 2 }}>
          Admin Panel
        </div>
      </div>

      {/* Nav */}
      <nav style={{ flex: 1, padding: '12px 12px' }}>
        {visible.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.to === '/'}
            style={({ isActive }) => ({
              display: 'flex',
              alignItems: 'center',
              gap: 10,
              padding: '9px 12px',
              borderRadius: 'var(--radius-md)',
              marginBottom: 2,
              fontSize: 14,
              fontWeight: 500,
              color: isActive ? '#f8fafc' : '#94a3b8',
              background: isActive ? '#1e40af' : 'transparent',
              transition: 'all var(--transition)',
              textDecoration: 'none',
            })}
          >
            <span>{item.icon}</span>
            <span>{item.label}</span>
          </NavLink>
        ))}
      </nav>

      {/* User info */}
      {user && (
        <div
          style={{
            padding: '12px 16px',
            borderTop: '1px solid #1e293b',
          }}
        >
          <div style={{ fontSize: 13, color: '#94a3b8', marginBottom: 4 }}>
            {user.name || user.slug}
          </div>
          <div style={{ fontSize: 11, color: '#475569', marginBottom: 8 }}>
            {isAdmin ? 'Administrator' : isTenantOwner ? 'Tenant Owner' : 'Vendor'}
          </div>
          <button
            onClick={handleLogout}
            style={{
              background: '#1e293b',
              border: 'none',
              color: '#94a3b8',
              padding: '6px 12px',
              borderRadius: 'var(--radius-sm)',
              fontSize: 12,
              cursor: 'pointer',
              width: '100%',
            }}
          >
            Logout
          </button>
        </div>
      )}
    </aside>
  );
}
