import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';
import { login as apiLogin, logout as apiLogout, getCurrentUser } from '../api/auth.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Bootstrap: check if a valid token exists
  useEffect(() => {
    const token = sessionStorage.getItem('wp_token');
    if (!token) {
      setLoading(false);
      return;
    }
    getCurrentUser()
      .then(setUser)
      .catch(() => {
        sessionStorage.removeItem('wp_token');
      })
      .finally(() => setLoading(false));
  }, []);

  // Listen for the global logout event (e.g. 401 response)
  useEffect(() => {
    const handler = () => {
      setUser(null);
    };
    window.addEventListener('auth:logout', handler);
    return () => window.removeEventListener('auth:logout', handler);
  }, []);

  const login = useCallback(async (credentials) => {
    setError(null);
    try {
      await apiLogin(credentials);
      const userData = await getCurrentUser();
      setUser(userData);
      return userData;
    } catch (err) {
      const msg =
        err?.response?.data?.message ||
        err?.message ||
        'Login failed. Please check your credentials.';
      setError(msg);
      throw new Error(msg);
    }
  }, []);

  const logout = useCallback(async () => {
    await apiLogout();
    setUser(null);
  }, []);

  const isAdmin = user?.capabilities?.manage_options === true;
  const isTenantOwner =
    user?.capabilities?.poradnik_specialist === true ||
    user?.roles?.includes('specialist');
  const isVendorUser =
    user?.capabilities?.poradnik_advertiser === true ||
    user?.roles?.includes('advertiser');

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        error,
        isAdmin,
        isTenantOwner,
        isVendorUser,
        login,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
}
