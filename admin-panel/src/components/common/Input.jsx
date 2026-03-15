import React from 'react';

export default function Input({
  label,
  error,
  id,
  type = 'text',
  required = false,
  style = {},
  containerStyle = {},
  ...rest
}) {
  const inputId = id || `input-${label?.toLowerCase().replace(/\s+/g, '-')}`;
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 4, ...containerStyle }}>
      {label && (
        <label
          htmlFor={inputId}
          style={{ fontSize: 13, fontWeight: 500, color: 'var(--color-text)' }}
        >
          {label}
          {required && <span style={{ color: 'var(--color-danger)', marginLeft: 2 }}>*</span>}
        </label>
      )}
      <input
        id={inputId}
        type={type}
        required={required}
        style={{
          padding: '8px 12px',
          borderRadius: 'var(--radius-md)',
          border: `1px solid ${error ? 'var(--color-danger)' : 'var(--color-border)'}`,
          background: 'var(--color-surface)',
          color: 'var(--color-text)',
          fontSize: 14,
          width: '100%',
          outline: 'none',
          transition: 'border-color var(--transition)',
          ...style,
        }}
        {...rest}
      />
      {error && (
        <span style={{ fontSize: 12, color: 'var(--color-danger)' }}>{error}</span>
      )}
    </div>
  );
}
