import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from 'react-query';
import { Toaster } from 'react-hot-toast';

import { AuthProvider, useAuth } from './context/AuthContext.jsx';
import Layout from './components/Layout/Layout.jsx';
import { FullPageSpinner } from './components/common/Spinner.jsx';

import LoginPage    from './pages/Login.jsx';
import DashboardPage from './pages/Dashboard.jsx';
import TenantsPage   from './pages/Tenants.jsx';
import VendorsPage   from './pages/Vendors.jsx';
import SiteConfigPage from './pages/SiteConfig.jsx';
import UsersPage     from './pages/Users.jsx';
import ArticlesPage  from './pages/Articles.jsx';
import AnalyticsPage from './pages/Analytics.jsx';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      refetchOnWindowFocus: false,
    },
  },
});

function ProtectedRoute({ children, adminOnly = false }) {
  const { user, loading, isAdmin } = useAuth();
  if (loading) return <FullPageSpinner />;
  if (!user) return <Navigate to="/login" replace />;
  if (adminOnly && !isAdmin) return <Navigate to="/" replace />;
  return children;
}

function AppRoutes() {
  const { user, loading } = useAuth();
  if (loading) return <FullPageSpinner />;

  return (
    <Routes>
      <Route path="/login" element={user ? <Navigate to="/" replace /> : <LoginPage />} />

      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Layout>
              <DashboardPage />
            </Layout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/tenants"
        element={
          <ProtectedRoute adminOnly>
            <Layout>
              <TenantsPage />
            </Layout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/vendors"
        element={
          <ProtectedRoute>
            <Layout>
              <VendorsPage />
            </Layout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/site-config"
        element={
          <ProtectedRoute adminOnly>
            <Layout>
              <SiteConfigPage />
            </Layout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/users"
        element={
          <ProtectedRoute adminOnly>
            <Layout>
              <UsersPage />
            </Layout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/articles"
        element={
          <ProtectedRoute>
            <Layout>
              <ArticlesPage />
            </Layout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/analytics"
        element={
          <ProtectedRoute>
            <Layout>
              <AnalyticsPage />
            </Layout>
          </ProtectedRoute>
        }
      />

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter basename="/admin-panel">
        <AuthProvider>
          <AppRoutes />
          <Toaster
            position="top-right"
            toastOptions={{
              style: {
                fontFamily: 'var(--font-sans)',
                fontSize: 14,
              },
            }}
          />
        </AuthProvider>
      </BrowserRouter>
    </QueryClientProvider>
  );
}
