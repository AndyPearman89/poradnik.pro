import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { tenantApi } from '../api/tenants.js';
import Card from '../components/common/Card.jsx';
import Button from '../components/common/Button.jsx';
import Badge from '../components/common/Badge.jsx';
import Modal from '../components/common/Modal.jsx';
import Input from '../components/common/Input.jsx';
import Spinner from '../components/common/Spinner.jsx';
import toast from 'react-hot-toast';

const EMPTY_FORM = {
  name: '',
  domain: '',
  email: '',
  plan: 'free',
  status: 'active',
  description: '',
};

export default function TenantsPage() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null); // null = new tenant
  const [form, setForm] = useState(EMPTY_FORM);
  const [deleteConfirm, setDeleteConfirm] = useState(null);

  const { data, isLoading, error } = useQuery(
    ['tenants', search],
    () => tenantApi.list({ search }),
    { retry: 1 }
  );

  const createMutation = useMutation((payload) => tenantApi.create(payload), {
    onSuccess: () => {
      qc.invalidateQueries('tenants');
      setModalOpen(false);
      toast.success('Tenant created successfully!');
    },
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to create tenant.'),
  });

  const updateMutation = useMutation(({ id, payload }) => tenantApi.update(id, payload), {
    onSuccess: () => {
      qc.invalidateQueries('tenants');
      setModalOpen(false);
      toast.success('Tenant updated!');
    },
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to update tenant.'),
  });

  const deleteMutation = useMutation((id) => tenantApi.delete(id), {
    onSuccess: () => {
      qc.invalidateQueries('tenants');
      setDeleteConfirm(null);
      toast.success('Tenant deleted.');
    },
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to delete tenant.'),
  });

  const toggleMutation = useMutation(({ id, active }) => tenantApi.toggleStatus(id, active), {
    onSuccess: () => qc.invalidateQueries('tenants'),
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to update status.'),
  });

  function openNew() {
    setEditing(null);
    setForm(EMPTY_FORM);
    setModalOpen(true);
  }

  function openEdit(tenant) {
    setEditing(tenant);
    setForm({
      name: tenant.name || '',
      domain: tenant.domain || '',
      email: tenant.email || '',
      plan: tenant.plan || 'free',
      status: tenant.status || 'active',
      description: tenant.description || '',
    });
    setModalOpen(true);
  }

  function handleFormChange(field, value) {
    setForm((prev) => ({ ...prev, [field]: value }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    if (editing) {
      updateMutation.mutate({ id: editing.id, payload: form });
    } else {
      createMutation.mutate(form);
    }
  }

  const isSaving = createMutation.isLoading || updateMutation.isLoading;
  const tenants = data?.items || data || [];

  return (
    <div className="fade-in" style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
      {/* Toolbar */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
        <Input
          placeholder="Search tenants..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          style={{ maxWidth: 280 }}
        />
        <div style={{ flex: 1 }} />
        <Button onClick={openNew}>+ Add Tenant</Button>
      </div>

      <Card>
        {isLoading && (
          <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}>
            <Spinner />
          </div>
        )}
        {error && (
          <div style={{ color: 'var(--color-danger)', padding: 20 }}>
            Failed to load tenants. Check your permissions.
          </div>
        )}
        {!isLoading && !error && (
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 14 }}>
            <thead>
              <tr style={{ background: 'var(--color-bg)' }}>
                {['ID', 'Name', 'Domain', 'Email', 'Plan', 'Status', 'Actions'].map((h) => (
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
              {tenants.length === 0 && (
                <tr>
                  <td colSpan={7} style={{ textAlign: 'center', padding: 40, color: 'var(--color-text-muted)' }}>
                    No tenants found.
                  </td>
                </tr>
              )}
              {tenants.map((tenant) => (
                <tr
                  key={tenant.id}
                  style={{ borderBottom: '1px solid var(--color-border)' }}
                  onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--color-bg)')}
                  onMouseLeave={(e) => (e.currentTarget.style.background = '')}
                >
                  <td style={{ padding: '10px 12px', color: 'var(--color-text-muted)' }}>
                    #{tenant.id}
                  </td>
                  <td style={{ padding: '10px 12px', fontWeight: 600 }}>{tenant.name}</td>
                  <td style={{ padding: '10px 12px' }}>
                    <a href={`https://${tenant.domain}`} target="_blank" rel="noreferrer" style={{ color: 'var(--color-primary)' }}>
                      {tenant.domain}
                    </a>
                  </td>
                  <td style={{ padding: '10px 12px' }}>{tenant.email}</td>
                  <td style={{ padding: '10px 12px' }}>
                    <Badge label={tenant.plan || 'free'} status="info" />
                  </td>
                  <td style={{ padding: '10px 12px' }}>
                    <Badge
                      label={tenant.status}
                      status={tenant.status === 'active' ? 'active' : 'inactive'}
                    />
                  </td>
                  <td style={{ padding: '10px 12px' }}>
                    <div style={{ display: 'flex', gap: 6 }}>
                      <Button size="sm" variant="secondary" onClick={() => openEdit(tenant)}>
                        Edit
                      </Button>
                      <Button
                        size="sm"
                        variant={tenant.status === 'active' ? 'ghost' : 'success'}
                        onClick={() =>
                          toggleMutation.mutate({ id: tenant.id, active: tenant.status !== 'active' })
                        }
                      >
                        {tenant.status === 'active' ? 'Deactivate' : 'Activate'}
                      </Button>
                      <Button
                        size="sm"
                        variant="danger"
                        onClick={() => setDeleteConfirm(tenant)}
                      >
                        Delete
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>

      {/* Add / Edit Modal */}
      <Modal
        isOpen={modalOpen}
        title={editing ? `Edit Tenant – ${editing.name}` : 'Add New Tenant'}
        onClose={() => setModalOpen(false)}
        footer={
          <>
            <Button variant="secondary" onClick={() => setModalOpen(false)}>
              Cancel
            </Button>
            <Button loading={isSaving} onClick={handleSubmit} type="submit">
              {editing ? 'Save Changes' : 'Create Tenant'}
            </Button>
          </>
        }
      >
        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
          <Input
            label="Tenant Name"
            required
            value={form.name}
            onChange={(e) => handleFormChange('name', e.target.value)}
          />
          <Input
            label="Domain"
            placeholder="example.com"
            required
            value={form.domain}
            onChange={(e) => handleFormChange('domain', e.target.value)}
          />
          <Input
            label="Contact Email"
            type="email"
            required
            value={form.email}
            onChange={(e) => handleFormChange('email', e.target.value)}
          />
          <div style={{ display: 'flex', gap: 12 }}>
            <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 4 }}>
              <label style={{ fontSize: 13, fontWeight: 500 }}>Plan</label>
              <select
                value={form.plan}
                onChange={(e) => handleFormChange('plan', e.target.value)}
                style={{
                  padding: '8px 12px',
                  borderRadius: 'var(--radius-md)',
                  border: '1px solid var(--color-border)',
                  fontSize: 14,
                }}
              >
                {['free', 'basic', 'pro', 'enterprise'].map((p) => (
                  <option key={p} value={p}>{p.charAt(0).toUpperCase() + p.slice(1)}</option>
                ))}
              </select>
            </div>
            <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 4 }}>
              <label style={{ fontSize: 13, fontWeight: 500 }}>Status</label>
              <select
                value={form.status}
                onChange={(e) => handleFormChange('status', e.target.value)}
                style={{
                  padding: '8px 12px',
                  borderRadius: 'var(--radius-md)',
                  border: '1px solid var(--color-border)',
                  fontSize: 14,
                }}
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
              </select>
            </div>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
            <label style={{ fontSize: 13, fontWeight: 500 }}>Description</label>
            <textarea
              value={form.description}
              onChange={(e) => handleFormChange('description', e.target.value)}
              rows={3}
              style={{
                padding: '8px 12px',
                borderRadius: 'var(--radius-md)',
                border: '1px solid var(--color-border)',
                fontSize: 14,
                resize: 'vertical',
              }}
            />
          </div>
        </form>
      </Modal>

      {/* Delete Confirm Modal */}
      <Modal
        isOpen={!!deleteConfirm}
        title="Confirm Delete"
        onClose={() => setDeleteConfirm(null)}
        width={400}
        footer={
          <>
            <Button variant="secondary" onClick={() => setDeleteConfirm(null)}>
              Cancel
            </Button>
            <Button
              variant="danger"
              loading={deleteMutation.isLoading}
              onClick={() => deleteMutation.mutate(deleteConfirm?.id)}
            >
              Delete Tenant
            </Button>
          </>
        }
      >
        <p>
          Are you sure you want to delete tenant <strong>{deleteConfirm?.name}</strong>? This
          action cannot be undone.
        </p>
      </Modal>
    </div>
  );
}
