import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext.jsx';
import Button from '../common/Button.jsx';
import Input from '../common/Input.jsx';

export default function LoginForm() {
  const { login, error } = useAuth();
  const navigate = useNavigate();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [localError, setLocalError] = useState('');

  async function handleSubmit(e) {
    e.preventDefault();
    setLocalError('');
    if (!username.trim() || !password) {
      setLocalError('Username and password are required.');
      return;
    }
    setLoading(true);
    try {
      await login({ username: username.trim(), password });
      navigate('/');
    } catch (err) {
      setLocalError(err.message || 'Login failed.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%)',
      }}
    >
      <div
        style={{
          background: 'var(--color-surface)',
          padding: '40px 36px',
          borderRadius: 'var(--radius-xl)',
          boxShadow: 'var(--shadow-lg)',
          width: '100%',
          maxWidth: 400,
        }}
      >
        <div style={{ textAlign: 'center', marginBottom: 28 }}>
          <div style={{ fontSize: 36, marginBottom: 8 }}>🌿</div>
          <h2 style={{ fontSize: 22, fontWeight: 700, marginBottom: 4 }}>
            Poradnik.pro
          </h2>
          <p style={{ color: 'var(--color-text-muted)', fontSize: 13 }}>
            Admin Panel – Sign in to continue
          </p>
        </div>

        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <Input
            label="Username"
            id="username"
            type="text"
            required
            autoFocus
            autoComplete="username"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
          />
          <Input
            label="Password"
            id="password"
            type="password"
            required
            autoComplete="current-password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />

          {(localError || error) && (
            <div
              style={{
                background: '#fee2e2',
                color: 'var(--color-danger)',
                padding: '10px 14px',
                borderRadius: 'var(--radius-md)',
                fontSize: 13,
              }}
            >
              {localError || error}
            </div>
          )}

          <Button type="submit" loading={loading} size="lg" style={{ width: '100%', justifyContent: 'center' }}>
            Sign In
          </Button>
        </form>

        <p style={{ textAlign: 'center', marginTop: 20, fontSize: 12, color: 'var(--color-text-light)' }}>
          Uses WordPress REST API authentication
        </p>
      </div>
    </div>
  );
}
