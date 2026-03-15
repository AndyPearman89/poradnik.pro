import React, { useState } from 'react';
import { useQuery } from 'react-query';
import { dashboardApi } from '../api/dashboard.js';
import Card from '../components/common/Card.jsx';
import Spinner from '../components/common/Spinner.jsx';
import {
  AreaChart,
  Area,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';

export default function AnalyticsPage() {
  const [period, setPeriod] = useState('daily');

  const { data, isLoading, error } = useQuery(
    ['analytics', period],
    () => dashboardApi.analytics({ period }),
    { retry: 1 }
  );

  const series = data?.series || [];
  const metrics = data?.metrics || {};
  const traffic = data?.traffic || {};

  const metricCards = [
    { label: 'Page Views',    value: traffic.pageviews,  color: 'var(--color-primary)' },
    { label: 'Sessions',      value: traffic.sessions,   color: 'var(--color-secondary)' },
    { label: 'Impressions',   value: metrics.impressions,color: 'var(--color-info)' },
    { label: 'Clicks',        value: metrics.clicks,     color: 'var(--color-success)' },
    { label: 'CTR',           value: metrics.ctr != null ? `${metrics.ctr}%` : '—', color: 'var(--color-warning)' },
    { label: 'Revenue (PLN)', value: metrics.revenue != null ? `${Number(metrics.revenue).toLocaleString()} zł` : '—', color: 'var(--color-danger)' },
  ];

  return (
    <div className="fade-in" style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
      {/* Period selector */}
      <div style={{ display: 'flex', gap: 8 }}>
        {['daily', 'weekly', 'monthly'].map((p) => (
          <button
            key={p}
            onClick={() => setPeriod(p)}
            style={{
              padding: '7px 16px',
              borderRadius: 'var(--radius-md)',
              border: '1px solid var(--color-border)',
              background: period === p ? 'var(--color-primary)' : 'var(--color-surface)',
              color: period === p ? '#fff' : 'var(--color-text)',
              fontWeight: 600,
              fontSize: 13,
              cursor: 'pointer',
            }}
          >
            {p.charAt(0).toUpperCase() + p.slice(1)}
          </button>
        ))}
      </div>

      {isLoading && (
        <div style={{ display: 'flex', justifyContent: 'center', padding: 60 }}>
          <Spinner size={40} />
        </div>
      )}

      {error && (
        <div style={{ color: 'var(--color-danger)', padding: 20, background: '#fee2e2', borderRadius: 8 }}>
          Failed to load analytics data.
        </div>
      )}

      {!isLoading && !error && (
        <>
          {/* Metric cards */}
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fill, minmax(170px, 1fr))',
              gap: 14,
            }}
          >
            {metricCards.map((m) => (
              <div
                key={m.label}
                style={{
                  background: 'var(--color-surface)',
                  border: '1px solid var(--color-border)',
                  borderLeft: `4px solid ${m.color}`,
                  borderRadius: 'var(--radius-md)',
                  padding: '16px',
                }}
              >
                <div style={{ fontSize: 12, color: 'var(--color-text-muted)', fontWeight: 500 }}>
                  {m.label}
                </div>
                <div style={{ fontSize: 22, fontWeight: 700, marginTop: 4 }}>
                  {m.value ?? '—'}
                </div>
              </div>
            ))}
          </div>

          {/* Time series chart */}
          {series.length > 0 && (
            <Card title="Impressions & Clicks Over Time">
              <ResponsiveContainer width="100%" height={260}>
                <AreaChart data={series}>
                  <defs>
                    <linearGradient id="gradImpressions" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="var(--color-primary)" stopOpacity={0.3} />
                      <stop offset="95%" stopColor="var(--color-primary)" stopOpacity={0} />
                    </linearGradient>
                    <linearGradient id="gradClicks" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="var(--color-success)" stopOpacity={0.3} />
                      <stop offset="95%" stopColor="var(--color-success)" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" />
                  <XAxis dataKey="date" tick={{ fontSize: 11 }} />
                  <YAxis tick={{ fontSize: 11 }} />
                  <Tooltip />
                  <Legend />
                  <Area
                    type="monotone"
                    dataKey="impressions"
                    stroke="var(--color-primary)"
                    fill="url(#gradImpressions)"
                    strokeWidth={2}
                  />
                  <Area
                    type="monotone"
                    dataKey="clicks"
                    stroke="var(--color-success)"
                    fill="url(#gradClicks)"
                    strokeWidth={2}
                  />
                </AreaChart>
              </ResponsiveContainer>
            </Card>
          )}

          {/* Revenue bar chart */}
          {series.some((s) => s.revenue != null) && (
            <Card title="Revenue Over Time">
              <ResponsiveContainer width="100%" height={220}>
                <BarChart data={series}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" />
                  <XAxis dataKey="date" tick={{ fontSize: 11 }} />
                  <YAxis tick={{ fontSize: 11 }} />
                  <Tooltip />
                  <Bar dataKey="revenue" fill="var(--color-warning)" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </Card>
          )}
        </>
      )}
    </div>
  );
}
