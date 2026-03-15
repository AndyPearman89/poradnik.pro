import React from 'react';

export default function Card({ children, title, action, style = {} }) {
  return (
    <div
      style={{
        background: 'var(--color-surface)',
        borderRadius: 'var(--radius-lg)',
        boxShadow: 'var(--shadow-sm)',
        border: '1px solid var(--color-border)',
        overflow: 'hidden',
        ...style,
      }}
    >
      {(title || action) && (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            padding: '14px 20px',
            borderBottom: '1px solid var(--color-border)',
          }}
        >
          {title && (
            <h3 style={{ fontSize: 15, fontWeight: 600, color: 'var(--color-text)' }}>
              {title}
            </h3>
          )}
          {action && <div>{action}</div>}
        </div>
      )}
      <div style={{ padding: 20 }}>{children}</div>
    </div>
  );
}
