import React from 'react';

export default function StatsCard({ label, value, icon, trend, color = 'var(--color-primary)' }) {
  return (
    <div
      style={{
        background: 'var(--color-surface)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-lg)',
        padding: '20px',
        display: 'flex',
        flexDirection: 'column',
        gap: 8,
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <span style={{ fontSize: 13, color: 'var(--color-text-muted)', fontWeight: 500 }}>
          {label}
        </span>
        <div
          style={{
            width: 36,
            height: 36,
            borderRadius: 'var(--radius-md)',
            background: `${color}20`,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: 18,
          }}
        >
          {icon}
        </div>
      </div>
      <div style={{ fontSize: 28, fontWeight: 700, color: 'var(--color-text)' }}>
        {value ?? '—'}
      </div>
      {trend !== undefined && (
        <div
          style={{
            fontSize: 12,
            color: trend >= 0 ? 'var(--color-success)' : 'var(--color-danger)',
            fontWeight: 500,
          }}
        >
          {trend >= 0 ? '▲' : '▼'} {Math.abs(trend)}% vs last month
        </div>
      )}
    </div>
  );
}
