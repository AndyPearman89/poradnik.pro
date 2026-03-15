import React from 'react';

const variants = {
  primary: {
    background: 'var(--color-primary)',
    color: '#fff',
    border: 'none',
  },
  secondary: {
    background: 'var(--color-surface)',
    color: 'var(--color-text)',
    border: '1px solid var(--color-border)',
  },
  danger: {
    background: 'var(--color-danger)',
    color: '#fff',
    border: 'none',
  },
  ghost: {
    background: 'transparent',
    color: 'var(--color-primary)',
    border: 'none',
  },
  success: {
    background: 'var(--color-success)',
    color: '#fff',
    border: 'none',
  },
};

const sizes = {
  sm: { padding: '6px 12px', fontSize: '12px', borderRadius: 'var(--radius-sm)' },
  md: { padding: '8px 16px', fontSize: '14px', borderRadius: 'var(--radius-md)' },
  lg: { padding: '10px 20px', fontSize: '15px', borderRadius: 'var(--radius-md)' },
};

export default function Button({
  children,
  variant = 'primary',
  size = 'md',
  onClick,
  type = 'button',
  disabled = false,
  loading = false,
  style = {},
  className = '',
  ...rest
}) {
  const variantStyle = variants[variant] || variants.primary;
  const sizeStyle = sizes[size] || sizes.md;

  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled || loading}
      style={{
        ...variantStyle,
        ...sizeStyle,
        display: 'inline-flex',
        alignItems: 'center',
        gap: '6px',
        fontWeight: 500,
        cursor: disabled || loading ? 'not-allowed' : 'pointer',
        opacity: disabled ? 0.6 : 1,
        transition: 'opacity var(--transition), background var(--transition)',
        ...style,
      }}
      className={className}
      {...rest}
    >
      {loading && (
        <span
          className="spin"
          style={{
            display: 'inline-block',
            width: 14,
            height: 14,
            border: '2px solid currentColor',
            borderTopColor: 'transparent',
            borderRadius: '50%',
          }}
        />
      )}
      {children}
    </button>
  );
}
