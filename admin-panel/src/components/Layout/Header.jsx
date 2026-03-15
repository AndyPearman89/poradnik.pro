import React from 'react';
import { useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext.jsx';

const titles = {
  '/':            'Dashboard',
  '/tenants':     'Tenant Management',
  '/vendors':     'Vendor Management',
  '/site-config': 'Site Configuration',
  '/users':       'Users & Role Management',
  '/articles':    'Articles',
  '/analytics':   'Analytics',
};

export default function Header() {
  const { pathname } = useLocation();
  const { user } = useAuth();
  const title = titles[pathname] || 'Admin Panel';

  return (
    <header
      style={{
        height: 'var(--header-height)',
        background: 'var(--color-surface)',
        borderBottom: '1px solid var(--color-border)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: '0 24px',
        position: 'sticky',
        top: 0,
        zIndex: 100,
        boxShadow: 'var(--shadow-sm)',
      }}
    >
      <h1 style={{ fontSize: 18, fontWeight: 600, color: 'var(--color-text)' }}>
        {title}
      </h1>

      {user && (
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <div
            style={{
              width: 34,
              height: 34,
              borderRadius: '50%',
              background: 'var(--color-primary)',
              color: '#fff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontWeight: 700,
              fontSize: 14,
            }}
          >
            {(user.name || user.slug || 'U')[0].toUpperCase()}
          </div>
          <div>
            <div style={{ fontSize: 13, fontWeight: 600 }}>{user.name || user.slug}</div>
            <div style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>
              {user.email}
            </div>
          </div>
        </div>
      )}
    </header>
  );
}
