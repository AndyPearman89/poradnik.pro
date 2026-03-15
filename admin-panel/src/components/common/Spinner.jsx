import React from 'react';

export default function Spinner({ size = 32, style = {} }) {
  return (
    <div
      className="spin"
      style={{
        width: size,
        height: size,
        borderRadius: '50%',
        border: '3px solid var(--color-border)',
        borderTopColor: 'var(--color-primary)',
        ...style,
      }}
    />
  );
}

export function FullPageSpinner() {
  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        height: '100vh',
        width: '100vw',
      }}
    >
      <Spinner size={48} />
    </div>
  );
}
