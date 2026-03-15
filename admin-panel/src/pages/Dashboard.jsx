import React from 'react';
import { useQuery } from 'react-query';
import { dashboardApi } from '../api/dashboard.js';
import StatsCard from '../components/Dashboard/StatsCard.jsx';
import Card from '../components/common/Card.jsx';
import Spinner from '../components/common/Spinner.jsx';
import { useAuth } from '../context/AuthContext.jsx';
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';

export default function DashboardPage() {
  const { isAdmin } = useAuth();
  const { data, isLoading, error } = useQuery('dashboard-overview', dashboardApi.overview, {
    retry: 1,
  });

  const { data: analytics } = useQuery('dashboard-analytics', () =>
    dashboardApi.analytics({ period: 'daily' }), { retry: 1 }
  );

  if (isLoading) {
    return (
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 300 }}>
        <Spinner size={40} />
      </div>
    );
  }

  if (error) {
    return (
      <div
        style={{
          padding: 20,
          background: '#fee2e2',
          borderRadius: 'var(--radius-md)',
          color: 'var(--color-danger)',
        }}
      >
        Failed to load dashboard data. Please check your connection and permissions.
      </div>
    );
  }

  const overview = data?.overview || {};
  const traffic = data?.traffic || {};
  const series = analytics?.series || [];

  const statsItems = isAdmin
    ? [
        { label: 'Total Users',     value: overview.users,    icon: '👥', color: 'var(--color-primary)' },
        { label: 'Active Tenants',  value: overview.tenants,  icon: '🏢', color: 'var(--color-secondary)' },
        { label: 'Vendors',         value: overview.vendors,  icon: '🏪', color: 'var(--color-info)' },
        { label: 'Articles',        value: overview.articles, icon: '📝', color: 'var(--color-success)' },
        { label: 'Ad Campaigns',    value: overview.campaigns,icon: '📣', color: 'var(--color-warning)' },
        { label: 'Revenue (PLN)',   value: overview.revenue != null ? `${Number(overview.revenue).toLocaleString()} zł` : '—', icon: '💰', color: 'var(--color-success)' },
      ]
    : [
        { label: 'My Campaigns',    value: overview.campaigns, icon: '📣', color: 'var(--color-primary)' },
        { label: 'Impressions',     value: overview.impressions, icon: '👁️', color: 'var(--color-info)' },
        { label: 'Clicks',          value: overview.clicks, icon: '🖱️', color: 'var(--color-secondary)' },
        { label: 'Spent (PLN)',     value: overview.spent != null ? `${Number(overview.spent).toLocaleString()} zł` : '—', icon: '💰', color: 'var(--color-warning)' },
      ];

  return (
    <div className="fade-in" style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>
      {/* Stats Grid */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
          gap: 16,
        }}
      >
        {statsItems.map((s) => (
          <StatsCard key={s.label} {...s} />
        ))}
      </div>

      {/* Traffic Chart */}
      {series.length > 0 && (
        <Card title="Traffic / Impressions (last 30 days)">
          <ResponsiveContainer width="100%" height={220}>
            <AreaChart data={series}>
              <defs>
                <linearGradient id="colorImpressions" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="var(--color-primary)" stopOpacity={0.3} />
                  <stop offset="95%" stopColor="var(--color-primary)" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" />
              <XAxis dataKey="date" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 11 }} />
              <Tooltip />
              <Area
                type="monotone"
                dataKey="impressions"
                stroke="var(--color-primary)"
                fill="url(#colorImpressions)"
                strokeWidth={2}
              />
            </AreaChart>
          </ResponsiveContainer>
        </Card>
      )}

      {/* Quick Links */}
      <Card title="Quick Access">
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))',
            gap: 12,
          }}
        >
          {[
            { href: '/tenants',     icon: '🏢', label: 'Manage Tenants' },
            { href: '/vendors',     icon: '🏪', label: 'Manage Vendors' },
            { href: '/site-config', icon: '⚙️',  label: 'Site Config' },
            { href: '/users',       icon: '👥', label: 'Users & Roles' },
            { href: '/articles',    icon: '📝', label: 'Articles' },
            { href: '/analytics',   icon: '📊', label: 'Analytics' },
          ].map((item) => (
            <a
              key={item.href}
              href={item.href}
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: 8,
                padding: '14px 10px',
                borderRadius: 'var(--radius-md)',
                border: '1px solid var(--color-border)',
                textAlign: 'center',
                fontSize: 13,
                fontWeight: 500,
                color: 'var(--color-text)',
                transition: 'all var(--transition)',
                cursor: 'pointer',
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.background = 'var(--color-primary-light)';
                e.currentTarget.style.borderColor = 'var(--color-primary)';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.background = '';
                e.currentTarget.style.borderColor = 'var(--color-border)';
              }}
            >
              <span style={{ fontSize: 24 }}>{item.icon}</span>
              {item.label}
            </a>
          ))}
        </div>
      </Card>
    </div>
  );
}
