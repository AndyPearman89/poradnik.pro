import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { vendorApi } from '../api/vendors.js';
import Card from '../components/common/Card.jsx';
import Button from '../components/common/Button.jsx';
import Badge from '../components/common/Badge.jsx';
import Modal from '../components/common/Modal.jsx';
import Input from '../components/common/Input.jsx';
import Spinner from '../components/common/Spinner.jsx';
import toast from 'react-hot-toast';

const EMPTY_FORM = {
  name: '',
  email: '',
  website: '',
  category: '',
  status: 'pending',
  description: '',
  commission_rate: '',
};

export default function VendorsPage() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(EMPTY_FORM);
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [metricsVendor, setMetricsVendor] = useState(null);

  const { data, isLoading, error } = useQuery(
    ['vendors', search, statusFilter],
    () => vendorApi.list({ search, status: statusFilter }),
    { retry: 1 }
  );

  const { data: metricsData } = useQuery(
    ['vendor-metrics', metricsVendor?.id],
    () => vendorApi.metrics(metricsVendor.id),
    { enabled: !!metricsVendor, retry: 1 }
  );

  const createMutation = useMutation((payload) => vendorApi.create(payload), {
    onSuccess: () => {
      qc.invalidateQueries('vendors');
      setModalOpen(false);
      toast.success('Vendor onboarded successfully!');
    },
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to create vendor.'),
  });

  const updateMutation = useMutation(({ id, payload }) => vendorApi.update(id, payload), {
    onSuccess: () => {
      qc.invalidateQueries('vendors');
      setModalOpen(false);
      toast.success('Vendor updated!');
    },
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to update vendor.'),
  });

  const deleteMutation = useMutation((id) => vendorApi.delete(id), {
    onSuccess: () => {
      qc.invalidateQueries('vendors');
      setDeleteConfirm(null);
      toast.success('Vendor removed.');
    },
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to delete vendor.'),
  });

  const approveMutation = useMutation((id) => vendorApi.approve(id), {
    onSuccess: () => {
      qc.invalidateQueries('vendors');
      toast.success('Vendor approved!');
    },
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to approve vendor.'),
  });

  const suspendMutation = useMutation((id) => vendorApi.suspend(id), {
    onSuccess: () => {
      qc.invalidateQueries('vendors');
      toast.success('Vendor suspended.');
    },
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to suspend vendor.'),
  });

  function openNew() {
    setEditing(null);
    setForm(EMPTY_FORM);
    setModalOpen(true);
  }

  function openEdit(vendor) {
    setEditing(vendor);
    setForm({
      name: vendor.name || '',
      email: vendor.email || '',
      website: vendor.website || '',
      category: vendor.category || '',
      status: vendor.status || 'pending',
      description: vendor.description || '',
      commission_rate: vendor.commission_rate ?? '',
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
  const vendors = data?.items || data || [];

  const statusBadgeMap = {
    active:    'active',
    inactive:  'inactive',
    pending:   'pending',
    approved:  'approved',
    suspended: 'suspended',
  };

  return (
    <div className="fade-in" style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
      {/* Toolbar */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
        <Input
          placeholder="Search vendors..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          style={{ maxWidth: 260 }}
        />
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          style={{
            padding: '8px 12px',
            borderRadius: 'var(--radius-md)',
            border: '1px solid var(--color-border)',
            fontSize: 14,
          }}
        >
          <option value="">All statuses</option>
          {['pending', 'active', 'approved', 'suspended', 'inactive'].map((s) => (
            <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>
          ))}
        </select>
        <div style={{ flex: 1 }} />
        <Button onClick={openNew}>+ Onboard Vendor</Button>
      </div>

      <Card>
        {isLoading && (
          <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}>
            <Spinner />
          </div>
        )}
        {error && (
          <div style={{ color: 'var(--color-danger)', padding: 20 }}>
            Failed to load vendors. Check your permissions.
          </div>
        )}
        {!isLoading && !error && (
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 14 }}>
            <thead>
              <tr style={{ background: 'var(--color-bg)' }}>
                {['ID', 'Name', 'Email', 'Category', 'Website', 'Commission', 'Status', 'Actions'].map((h) => (
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
              {vendors.length === 0 && (
                <tr>
                  <td colSpan={8} style={{ textAlign: 'center', padding: 40, color: 'var(--color-text-muted)' }}>
                    No vendors found.
                  </td>
                </tr>
              )}
              {vendors.map((vendor) => (
                <tr
                  key={vendor.id}
                  style={{ borderBottom: '1px solid var(--color-border)' }}
                  onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--color-bg)')}
                  onMouseLeave={(e) => (e.currentTarget.style.background = '')}
                >
                  <td style={{ padding: '10px 12px', color: 'var(--color-text-muted)' }}>#{vendor.id}</td>
                  <td style={{ padding: '10px 12px', fontWeight: 600 }}>{vendor.name}</td>
                  <td style={{ padding: '10px 12px' }}>{vendor.email}</td>
                  <td style={{ padding: '10px 12px' }}>{vendor.category || '—'}</td>
                  <td style={{ padding: '10px 12px' }}>
                    {vendor.website ? (
                      <a href={vendor.website} target="_blank" rel="noreferrer" style={{ color: 'var(--color-primary)' }}>
                        {vendor.website.replace(/^https?:\/\//, '')}
                      </a>
                    ) : '—'}
                  </td>
                  <td style={{ padding: '10px 12px' }}>
                    {vendor.commission_rate != null ? `${vendor.commission_rate}%` : '—'}
                  </td>
                  <td style={{ padding: '10px 12px' }}>
                    <Badge
                      label={vendor.status}
                      status={statusBadgeMap[vendor.status] || 'info'}
                    />
                  </td>
                  <td style={{ padding: '10px 12px' }}>
                    <div style={{ display: 'flex', gap: 4, flexWrap: 'wrap' }}>
                      <Button size="sm" variant="secondary" onClick={() => openEdit(vendor)}>
                        Edit
                      </Button>
                      {vendor.status === 'pending' && (
                        <Button
                          size="sm"
                          variant="success"
                          loading={approveMutation.isLoading}
                          onClick={() => approveMutation.mutate(vendor.id)}
                        >
                          Approve
                        </Button>
                      )}
                      {(vendor.status === 'active' || vendor.status === 'approved') && (
                        <Button
                          size="sm"
                          variant="ghost"
                          loading={suspendMutation.isLoading}
                          onClick={() => suspendMutation.mutate(vendor.id)}
                        >
                          Suspend
                        </Button>
                      )}
                      <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => setMetricsVendor(vendor)}
                      >
                        Metrics
                      </Button>
                      <Button
                        size="sm"
                        variant="danger"
                        onClick={() => setDeleteConfirm(vendor)}
                      >
                        Remove
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
        title={editing ? `Edit Vendor – ${editing.name}` : 'Onboard New Vendor'}
        onClose={() => setModalOpen(false)}
        footer={
          <>
            <Button variant="secondary" onClick={() => setModalOpen(false)}>Cancel</Button>
            <Button loading={isSaving} onClick={handleSubmit}>
              {editing ? 'Save Changes' : 'Onboard Vendor'}
            </Button>
          </>
        }
      >
        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
          <Input
            label="Vendor Name"
            required
            value={form.name}
            onChange={(e) => handleFormChange('name', e.target.value)}
          />
          <Input
            label="Email"
            type="email"
            required
            value={form.email}
            onChange={(e) => handleFormChange('email', e.target.value)}
          />
          <Input
            label="Website"
            placeholder="https://example.com"
            value={form.website}
            onChange={(e) => handleFormChange('website', e.target.value)}
          />
          <div style={{ display: 'flex', gap: 12 }}>
            <Input
              label="Category"
              value={form.category}
              onChange={(e) => handleFormChange('category', e.target.value)}
              containerStyle={{ flex: 1 }}
            />
            <Input
              label="Commission Rate (%)"
              type="number"
              min="0"
              max="100"
              step="0.1"
              value={form.commission_rate}
              onChange={(e) => handleFormChange('commission_rate', e.target.value)}
              containerStyle={{ flex: 1 }}
            />
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
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
              {['pending', 'active', 'approved', 'suspended', 'inactive'].map((s) => (
                <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>
              ))}
            </select>
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

      {/* Metrics Modal */}
      <Modal
        isOpen={!!metricsVendor}
        title={`Metrics – ${metricsVendor?.name}`}
        onClose={() => setMetricsVendor(null)}
        width={460}
      >
        {metricsData ? (
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
            {Object.entries(metricsData).map(([key, value]) => (
              <div
                key={key}
                style={{
                  padding: '12px 16px',
                  background: 'var(--color-bg)',
                  borderRadius: 'var(--radius-md)',
                  border: '1px solid var(--color-border)',
                }}
              >
                <div style={{ fontSize: 12, color: 'var(--color-text-muted)', fontWeight: 600 }}>
                  {key.replace(/_/g, ' ').toUpperCase()}
                </div>
                <div style={{ fontSize: 20, fontWeight: 700, marginTop: 4 }}>
                  {typeof value === 'number' ? value.toLocaleString() : value ?? '—'}
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div style={{ display: 'flex', justifyContent: 'center', padding: 30 }}>
            <Spinner />
          </div>
        )}
      </Modal>

      {/* Delete Confirm */}
      <Modal
        isOpen={!!deleteConfirm}
        title="Confirm Remove"
        onClose={() => setDeleteConfirm(null)}
        width={400}
        footer={
          <>
            <Button variant="secondary" onClick={() => setDeleteConfirm(null)}>Cancel</Button>
            <Button
              variant="danger"
              loading={deleteMutation.isLoading}
              onClick={() => deleteMutation.mutate(deleteConfirm?.id)}
            >
              Remove Vendor
            </Button>
          </>
        }
      >
        <p>
          Are you sure you want to remove vendor <strong>{deleteConfirm?.name}</strong>?
        </p>
      </Modal>
    </div>
  );
}
