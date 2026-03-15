import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import apiClient from '../api/client.js';
import Card from '../components/common/Card.jsx';
import Button from '../components/common/Button.jsx';
import Badge from '../components/common/Badge.jsx';
import Modal from '../components/common/Modal.jsx';
import Input from '../components/common/Input.jsx';
import Spinner from '../components/common/Spinner.jsx';
import toast from 'react-hot-toast';

const ROLES = ['administrator', 'specialist', 'advertiser', 'moderator', 'subscriber'];

const ROLE_INFO = {
  administrator: { label: 'Administrator',   icon: '👑', desc: 'Full access to all features.' },
  specialist:    { label: 'Tenant Owner',     icon: '🏢', desc: 'Manage own content and portal settings.' },
  advertiser:    { label: 'Vendor / Advertiser', icon: '🏪', desc: 'Manage own campaigns and products.' },
  moderator:     { label: 'Moderator',        icon: '🛡️', desc: 'Review and moderate content.' },
  subscriber:    { label: 'Subscriber',       icon: '👤', desc: 'Read-only access.' },
};

function fetchUsers(params = {}) {
  return apiClient
    .get('/wp/v2/users', { params: { per_page: 50, context: 'edit', ...params } })
    .then((r) => r.data);
}

function updateUserRole(userId, roles) {
  return apiClient.put(`/wp/v2/users/${userId}`, { roles }).then((r) => r.data);
}

export default function UsersPage() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [editingUser, setEditingUser] = useState(null);
  const [selectedRole, setSelectedRole] = useState('');

  const { data: users = [], isLoading, error } = useQuery(
    ['users', search, roleFilter],
    () => fetchUsers({ search, roles: roleFilter || undefined }),
    { retry: 1 }
  );

  const updateMutation = useMutation(
    ({ id, roles }) => updateUserRole(id, roles),
    {
      onSuccess: () => {
        qc.invalidateQueries('users');
        setEditingUser(null);
        toast.success('User role updated!');
      },
      onError: (err) => toast.error(err?.response?.data?.message || 'Failed to update role.'),
    }
  );

  function openEdit(user) {
    setEditingUser(user);
    setSelectedRole(user.roles?.[0] || 'subscriber');
  }

  return (
    <div className="fade-in" style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
      {/* Role legend */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))',
          gap: 12,
        }}
      >
        {ROLES.map((r) => {
          const info = ROLE_INFO[r] || { label: r, icon: '👤', desc: '' };
          return (
            <div
              key={r}
              style={{
                background: 'var(--color-surface)',
                border: '1px solid var(--color-border)',
                borderRadius: 'var(--radius-md)',
                padding: '14px 16px',
              }}
            >
              <div style={{ fontSize: 22, marginBottom: 4 }}>{info.icon}</div>
              <div style={{ fontWeight: 600, fontSize: 14 }}>{info.label}</div>
              <div style={{ fontSize: 12, color: 'var(--color-text-muted)', marginTop: 2 }}>
                {info.desc}
              </div>
            </div>
          );
        })}
      </div>

      {/* Toolbar */}
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
        <Input
          placeholder="Search users..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          style={{ maxWidth: 260 }}
        />
        <select
          value={roleFilter}
          onChange={(e) => setRoleFilter(e.target.value)}
          style={{
            padding: '8px 12px',
            borderRadius: 'var(--radius-md)',
            border: '1px solid var(--color-border)',
            fontSize: 14,
          }}
        >
          <option value="">All roles</option>
          {ROLES.map((r) => (
            <option key={r} value={r}>
              {ROLE_INFO[r]?.label || r}
            </option>
          ))}
        </select>
      </div>

      <Card>
        {isLoading && (
          <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}>
            <Spinner />
          </div>
        )}
        {error && (
          <div style={{ color: 'var(--color-danger)', padding: 20 }}>
            Failed to load users. Check your permissions.
          </div>
        )}
        {!isLoading && !error && (
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 14 }}>
            <thead>
              <tr style={{ background: 'var(--color-bg)' }}>
                {['ID', 'Name', 'Email', 'Role', 'Registered', 'Actions'].map((h) => (
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
              {users.length === 0 && (
                <tr>
                  <td colSpan={6} style={{ textAlign: 'center', padding: 40, color: 'var(--color-text-muted)' }}>
                    No users found.
                  </td>
                </tr>
              )}
              {users.map((user) => {
                const primaryRole = user.roles?.[0] || 'subscriber';
                const info = ROLE_INFO[primaryRole] || { label: primaryRole, icon: '👤' };
                const badgeStatus =
                  primaryRole === 'administrator' ? 'admin' :
                  primaryRole === 'specialist' ? 'tenant' :
                  primaryRole === 'advertiser' ? 'vendor' : 'info';

                return (
                  <tr
                    key={user.id}
                    style={{ borderBottom: '1px solid var(--color-border)' }}
                    onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--color-bg)')}
                    onMouseLeave={(e) => (e.currentTarget.style.background = '')}
                  >
                    <td style={{ padding: '10px 12px', color: 'var(--color-text-muted)' }}>#{user.id}</td>
                    <td style={{ padding: '10px 12px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                        <div
                          style={{
                            width: 30,
                            height: 30,
                            borderRadius: '50%',
                            background: 'var(--color-primary)',
                            color: '#fff',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: 12,
                            fontWeight: 700,
                            flexShrink: 0,
                          }}
                        >
                          {(user.name || 'U')[0].toUpperCase()}
                        </div>
                        <span style={{ fontWeight: 600 }}>{user.name}</span>
                      </div>
                    </td>
                    <td style={{ padding: '10px 12px' }}>{user.email}</td>
                    <td style={{ padding: '10px 12px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                        <span>{info.icon}</span>
                        <Badge label={info.label} status={badgeStatus} />
                      </div>
                    </td>
                    <td style={{ padding: '10px 12px', color: 'var(--color-text-muted)', fontSize: 12 }}>
                      {user.registered_date
                        ? new Date(user.registered_date).toLocaleDateString()
                        : '—'}
                    </td>
                    <td style={{ padding: '10px 12px' }}>
                      <Button size="sm" variant="secondary" onClick={() => openEdit(user)}>
                        Change Role
                      </Button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}
      </Card>

      {/* Edit Role Modal */}
      <Modal
        isOpen={!!editingUser}
        title={`Change Role – ${editingUser?.name}`}
        onClose={() => setEditingUser(null)}
        width={400}
        footer={
          <>
            <Button variant="secondary" onClick={() => setEditingUser(null)}>Cancel</Button>
            <Button
              loading={updateMutation.isLoading}
              onClick={() => updateMutation.mutate({ id: editingUser.id, roles: [selectedRole] })}
            >
              Save Role
            </Button>
          </>
        }
      >
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
          <p style={{ fontSize: 13, color: 'var(--color-text-muted)', marginBottom: 8 }}>
            Select a new role for <strong>{editingUser?.name}</strong>:
          </p>
          {ROLES.map((r) => {
            const info = ROLE_INFO[r] || { label: r, icon: '👤', desc: '' };
            return (
              <label
                key={r}
                style={{
                  display: 'flex',
                  alignItems: 'flex-start',
                  gap: 10,
                  padding: '10px 12px',
                  borderRadius: 'var(--radius-md)',
                  border: `1px solid ${selectedRole === r ? 'var(--color-primary)' : 'var(--color-border)'}`,
                  cursor: 'pointer',
                  background: selectedRole === r ? 'var(--color-primary-light)' : 'transparent',
                }}
              >
                <input
                  type="radio"
                  name="role"
                  value={r}
                  checked={selectedRole === r}
                  onChange={() => setSelectedRole(r)}
                  style={{ marginTop: 2 }}
                />
                <div>
                  <div style={{ fontWeight: 600, fontSize: 14 }}>
                    {info.icon} {info.label}
                  </div>
                  <div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>
                    {info.desc}
                  </div>
                </div>
              </label>
            );
          })}
        </div>
      </Modal>
    </div>
  );
}
