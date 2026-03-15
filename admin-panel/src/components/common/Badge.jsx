import React from 'react';

const statusColors = {
  active:   { bg: '#dcfce7', color: '#166534' },
  inactive: { bg: '#f1f5f9', color: '#475569' },
  pending:  { bg: '#fef9c3', color: '#854d0e' },
  approved: { bg: '#dcfce7', color: '#166534' },
  suspended:{ bg: '#fee2e2', color: '#991b1b' },
  draft:    { bg: '#f1f5f9', color: '#475569' },
  publish:  { bg: '#dcfce7', color: '#166534' },
  paused:   { bg: '#fef9c3', color: '#854d0e' },
  admin:    { bg: '#ede9fe', color: '#5b21b6' },
  vendor:   { bg: '#dbeafe', color: '#1e40af' },
  tenant:   { bg: '#fce7f3', color: '#9d174d' },
  info:     { bg: '#e0f2fe', color: '#075985' },
};

export default function Badge({ label, status }) {
  const style = statusColors[status] || statusColors.info;
  return (
    <span
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        padding: '2px 10px',
        borderRadius: 9999,
        fontSize: 12,
        fontWeight: 600,
        background: style.bg,
        color: style.color,
      }}
    >
      {label}
    </span>
  );
}
