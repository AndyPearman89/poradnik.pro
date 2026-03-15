import React from 'react';
import Sidebar from './Sidebar.jsx';
import Header from './Header.jsx';

export default function Layout({ children }) {
  return (
    <div style={{ display: 'flex', height: '100vh', overflow: 'hidden' }}>
      <Sidebar />
      <div style={{ flex: 1, display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
        <Header />
        <main
          style={{
            flex: 1,
            overflowY: 'auto',
            padding: '24px',
            background: 'var(--color-bg)',
          }}
        >
          {children}
        </main>
      </div>
    </div>
  );
}
