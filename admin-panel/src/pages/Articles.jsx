import React, { useState } from 'react';
import { useQuery } from 'react-query';
import { dashboardApi } from '../api/dashboard.js';
import Card from '../components/common/Card.jsx';
import Badge from '../components/common/Badge.jsx';
import Input from '../components/common/Input.jsx';
import Spinner from '../components/common/Spinner.jsx';

export default function ArticlesPage() {
  const [type, setType] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading, error } = useQuery(
    ['articles', type, status, page],
    () => dashboardApi.articles({ type, status, page, per_page: 20 }),
    { retry: 1, keepPreviousData: true }
  );

  const articles = data?.items || [];
  const total = data?.total || 0;
  const pages = data?.pages || 1;

  const typeColors = {
    post:    'info',
    guide:   'tenant',
    ranking: 'vendor',
    review:  'admin',
  };

  return (
    <div className="fade-in" style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
      {/* Filters */}
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
        <select
          value={type}
          onChange={(e) => { setType(e.target.value); setPage(1); }}
          style={{ padding: '8px 12px', borderRadius: 'var(--radius-md)', border: '1px solid var(--color-border)', fontSize: 14 }}
        >
          <option value="">All types</option>
          {['post', 'guide', 'ranking', 'review'].map((t) => (
            <option key={t} value={t}>{t.charAt(0).toUpperCase() + t.slice(1)}</option>
          ))}
        </select>
        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1); }}
          style={{ padding: '8px 12px', borderRadius: 'var(--radius-md)', border: '1px solid var(--color-border)', fontSize: 14 }}
        >
          <option value="">All statuses</option>
          {['publish', 'draft', 'pending'].map((s) => (
            <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>
          ))}
        </select>
        <div style={{ flex: 1 }} />
        <span style={{ fontSize: 13, color: 'var(--color-text-muted)' }}>
          {total} articles
        </span>
      </div>

      <Card>
        {isLoading && (
          <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}>
            <Spinner />
          </div>
        )}
        {error && (
          <div style={{ color: 'var(--color-danger)', padding: 20 }}>
            Failed to load articles.
          </div>
        )}
        {!isLoading && !error && (
          <>
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 14 }}>
              <thead>
                <tr style={{ background: 'var(--color-bg)' }}>
                  {['ID', 'Title', 'Type', 'Author', 'Status', 'Date', 'Link'].map((h) => (
                    <th
                      key={h}
                      style={{
                        textAlign: 'left',
                        padding: '10px 12px',
                        borderBottom: '1px solid var(--color-border)',
                        color: 'var(--color-text-muted)',
                        fontWeight: 600,
                        fontSize: 12,
                      }}
                    >
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {articles.length === 0 && (
                  <tr>
                    <td colSpan={7} style={{ textAlign: 'center', padding: 40, color: 'var(--color-text-muted)' }}>
                      No articles found.
                    </td>
                  </tr>
                )}
                {articles.map((article) => (
                  <tr
                    key={article.id}
                    style={{ borderBottom: '1px solid var(--color-border)' }}
                    onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--color-bg)')}
                    onMouseLeave={(e) => (e.currentTarget.style.background = '')}
                  >
                    <td style={{ padding: '10px 12px', color: 'var(--color-text-muted)' }}>#{article.id}</td>
                    <td style={{ padding: '10px 12px', fontWeight: 600, maxWidth: 300 }} className="truncate">
                      {article.title}
                    </td>
                    <td style={{ padding: '10px 12px' }}>
                      <Badge label={article.type} status={typeColors[article.type] || 'info'} />
                    </td>
                    <td style={{ padding: '10px 12px' }}>{article.author}</td>
                    <td style={{ padding: '10px 12px' }}>
                      <Badge
                        label={article.status}
                        status={article.status === 'publish' ? 'active' : article.status === 'draft' ? 'inactive' : 'pending'}
                      />
                    </td>
                    <td style={{ padding: '10px 12px', fontSize: 12, color: 'var(--color-text-muted)' }}>
                      {article.date ? new Date(article.date).toLocaleDateString() : '—'}
                    </td>
                    <td style={{ padding: '10px 12px' }}>
                      {article.url && (
                        <a
                          href={article.url}
                          target="_blank"
                          rel="noreferrer"
                          style={{ color: 'var(--color-primary)', fontSize: 12 }}
                        >
                          View ↗
                        </a>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            {/* Pagination */}
            {pages > 1 && (
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, padding: '16px 0 4px' }}>
                <button
                  disabled={page <= 1}
                  onClick={() => setPage((p) => p - 1)}
                  style={{
                    padding: '6px 12px',
                    border: '1px solid var(--color-border)',
                    borderRadius: 'var(--radius-sm)',
                    background: 'none',
                    cursor: page <= 1 ? 'not-allowed' : 'pointer',
                    opacity: page <= 1 ? 0.4 : 1,
                  }}
                >
                  ← Prev
                </button>
                <span style={{ fontSize: 13, color: 'var(--color-text-muted)' }}>
                  Page {page} of {pages}
                </span>
                <button
                  disabled={page >= pages}
                  onClick={() => setPage((p) => p + 1)}
                  style={{
                    padding: '6px 12px',
                    border: '1px solid var(--color-border)',
                    borderRadius: 'var(--radius-sm)',
                    background: 'none',
                    cursor: page >= pages ? 'not-allowed' : 'pointer',
                    opacity: page >= pages ? 0.4 : 1,
                  }}
                >
                  Next →
                </button>
              </div>
            )}
          </>
        )}
      </Card>
    </div>
  );
}
