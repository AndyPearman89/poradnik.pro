import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from 'react-query';
import { siteConfigApi } from '../api/siteConfig.js';
import Card from '../components/common/Card.jsx';
import Button from '../components/common/Button.jsx';
import Input from '../components/common/Input.jsx';
import Spinner from '../components/common/Spinner.jsx';
import toast from 'react-hot-toast';

const DEFAULT_CONFIG = {
  site_name: '',
  site_tagline: '',
  site_url: '',
  admin_email: '',
  language: 'pl_PL',
  timezone: 'Europe/Warsaw',
  theme: 'generatepress',
  logo_url: '',
  primary_color: '#2563eb',
  modules: {
    affiliate: true,
    ads_marketplace: true,
    ai_content: false,
    ai_image: false,
    rankings: true,
    reviews: true,
    sponsored: true,
    programmatic_seo: false,
    seo_automation: true,
  },
  multisite: {
    enabled: false,
    subdomain_install: true,
    max_sites: 10,
  },
};

export default function SiteConfigPage() {
  const [config, setConfig] = useState(DEFAULT_CONFIG);
  const [activeTab, setActiveTab] = useState('general');
  const [previewVisible, setPreviewVisible] = useState(false);

  const { data, isLoading } = useQuery('site-config', siteConfigApi.get, {
    retry: 1,
    onSuccess: (d) => {
      if (d) setConfig((prev) => ({ ...prev, ...d }));
    },
  });

  const saveMutation = useMutation(siteConfigApi.save, {
    onSuccess: () => toast.success('Configuration saved!'),
    onError: (err) => toast.error(err?.response?.data?.message || 'Failed to save config.'),
  });

  const resetMutation = useMutation(siteConfigApi.reset, {
    onSuccess: () => {
      setConfig(DEFAULT_CONFIG);
      toast.success('Reset to defaults.');
    },
    onError: () => toast.error('Failed to reset configuration.'),
  });

  function set(path, value) {
    const keys = path.split('.');
    setConfig((prev) => {
      const next = { ...prev };
      let cur = next;
      for (let i = 0; i < keys.length - 1; i++) {
        cur[keys[i]] = { ...cur[keys[i]] };
        cur = cur[keys[i]];
      }
      cur[keys[keys.length - 1]] = value;
      return next;
    });
  }

  const tabs = [
    { id: 'general',   label: '🌐 General' },
    { id: 'modules',   label: '🧩 Modules' },
    { id: 'multisite', label: '🏗️ Multisite' },
    { id: 'design',    label: '🎨 Design' },
  ];

  return (
    <div className="fade-in" style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
      {/* Tab Nav */}
      <div style={{ display: 'flex', gap: 2, borderBottom: '2px solid var(--color-border)', paddingBottom: 0 }}>
        {tabs.map((tab) => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id)}
            style={{
              padding: '10px 18px',
              border: 'none',
              borderBottom: activeTab === tab.id ? '2px solid var(--color-primary)' : '2px solid transparent',
              marginBottom: -2,
              background: 'none',
              fontSize: 14,
              fontWeight: 600,
              color: activeTab === tab.id ? 'var(--color-primary)' : 'var(--color-text-muted)',
              cursor: 'pointer',
            }}
          >
            {tab.label}
          </button>
        ))}
        <div style={{ flex: 1 }} />
        <Button
          variant="secondary"
          size="sm"
          onClick={() => setPreviewVisible((v) => !v)}
        >
          {previewVisible ? 'Hide Preview' : '👁 Preview'}
        </Button>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: previewVisible ? '1fr 400px' : '1fr', gap: 20 }}>
        {/* Config Form */}
        <div>
          {isLoading ? (
            <div style={{ display: 'flex', justifyContent: 'center', padding: 60 }}>
              <Spinner />
            </div>
          ) : (
            <>
              {activeTab === 'general' && (
                <Card title="General Settings">
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                    <Input
                      label="Site Name"
                      value={config.site_name}
                      onChange={(e) => set('site_name', e.target.value)}
                    />
                    <Input
                      label="Tagline"
                      value={config.site_tagline}
                      onChange={(e) => set('site_tagline', e.target.value)}
                    />
                    <Input
                      label="Site URL"
                      type="url"
                      value={config.site_url}
                      onChange={(e) => set('site_url', e.target.value)}
                    />
                    <Input
                      label="Admin Email"
                      type="email"
                      value={config.admin_email}
                      onChange={(e) => set('admin_email', e.target.value)}
                    />
                    <div style={{ display: 'flex', gap: 12 }}>
                      <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 4 }}>
                        <label style={{ fontSize: 13, fontWeight: 500 }}>Language</label>
                        <select
                          value={config.language}
                          onChange={(e) => set('language', e.target.value)}
                          style={{ padding: '8px 12px', borderRadius: 'var(--radius-md)', border: '1px solid var(--color-border)', fontSize: 14 }}
                        >
                          <option value="pl_PL">Polski (pl_PL)</option>
                          <option value="en_US">English (en_US)</option>
                          <option value="de_DE">Deutsch (de_DE)</option>
                        </select>
                      </div>
                      <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 4 }}>
                        <label style={{ fontSize: 13, fontWeight: 500 }}>Timezone</label>
                        <select
                          value={config.timezone}
                          onChange={(e) => set('timezone', e.target.value)}
                          style={{ padding: '8px 12px', borderRadius: 'var(--radius-md)', border: '1px solid var(--color-border)', fontSize: 14 }}
                        >
                          <option value="Europe/Warsaw">Europe/Warsaw</option>
                          <option value="Europe/London">Europe/London</option>
                          <option value="UTC">UTC</option>
                          <option value="America/New_York">America/New_York</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </Card>
              )}

              {activeTab === 'modules' && (
                <Card title="Platform Modules">
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 0 }}>
                    {Object.entries(config.modules).map(([key, enabled]) => (
                      <div
                        key={key}
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          padding: '12px 0',
                          borderBottom: '1px solid var(--color-border)',
                        }}
                      >
                        <div>
                          <div style={{ fontWeight: 600, fontSize: 14 }}>
                            {key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                          </div>
                        </div>
                        <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer' }}>
                          <span style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>
                            {enabled ? 'Enabled' : 'Disabled'}
                          </span>
                          <div
                            onClick={() => set(`modules.${key}`, !enabled)}
                            style={{
                              width: 42,
                              height: 24,
                              borderRadius: 12,
                              background: enabled ? 'var(--color-success)' : 'var(--color-border)',
                              position: 'relative',
                              cursor: 'pointer',
                              transition: 'background var(--transition)',
                            }}
                          >
                            <div
                              style={{
                                position: 'absolute',
                                top: 3,
                                left: enabled ? 20 : 3,
                                width: 18,
                                height: 18,
                                background: '#fff',
                                borderRadius: '50%',
                                boxShadow: '0 1px 3px rgba(0,0,0,.2)',
                                transition: 'left var(--transition)',
                              }}
                            />
                          </div>
                        </label>
                      </div>
                    ))}
                  </div>
                </Card>
              )}

              {activeTab === 'multisite' && (
                <Card title="Multisite Configuration">
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                    <div
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        padding: '12px 0',
                        borderBottom: '1px solid var(--color-border)',
                      }}
                    >
                      <div>
                        <div style={{ fontWeight: 600 }}>Enable Multisite</div>
                        <div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>
                          Activate WordPress Multisite / Network
                        </div>
                      </div>
                      <div
                        onClick={() => set('multisite.enabled', !config.multisite.enabled)}
                        style={{
                          width: 42,
                          height: 24,
                          borderRadius: 12,
                          background: config.multisite.enabled ? 'var(--color-success)' : 'var(--color-border)',
                          position: 'relative',
                          cursor: 'pointer',
                          transition: 'background var(--transition)',
                        }}
                      >
                        <div
                          style={{
                            position: 'absolute',
                            top: 3,
                            left: config.multisite.enabled ? 20 : 3,
                            width: 18,
                            height: 18,
                            background: '#fff',
                            borderRadius: '50%',
                            boxShadow: '0 1px 3px rgba(0,0,0,.2)',
                            transition: 'left var(--transition)',
                          }}
                        />
                      </div>
                    </div>

                    <div
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        padding: '12px 0',
                        borderBottom: '1px solid var(--color-border)',
                      }}
                    >
                      <div>
                        <div style={{ fontWeight: 600 }}>Subdomain Install</div>
                        <div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>
                          Use subdomains instead of subdirectories
                        </div>
                      </div>
                      <div
                        onClick={() => set('multisite.subdomain_install', !config.multisite.subdomain_install)}
                        style={{
                          width: 42,
                          height: 24,
                          borderRadius: 12,
                          background: config.multisite.subdomain_install ? 'var(--color-success)' : 'var(--color-border)',
                          position: 'relative',
                          cursor: 'pointer',
                          opacity: config.multisite.enabled ? 1 : 0.4,
                          transition: 'background var(--transition)',
                        }}
                      >
                        <div
                          style={{
                            position: 'absolute',
                            top: 3,
                            left: config.multisite.subdomain_install ? 20 : 3,
                            width: 18,
                            height: 18,
                            background: '#fff',
                            borderRadius: '50%',
                            boxShadow: '0 1px 3px rgba(0,0,0,.2)',
                            transition: 'left var(--transition)',
                          }}
                        />
                      </div>
                    </div>

                    <Input
                      label="Max Sites"
                      type="number"
                      min="1"
                      max="1000"
                      value={config.multisite.max_sites}
                      onChange={(e) => set('multisite.max_sites', parseInt(e.target.value, 10))}
                    />
                  </div>
                </Card>
              )}

              {activeTab === 'design' && (
                <Card title="Design & Branding">
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                    <Input
                      label="Theme"
                      value={config.theme}
                      onChange={(e) => set('theme', e.target.value)}
                    />
                    <Input
                      label="Logo URL"
                      type="url"
                      value={config.logo_url}
                      onChange={(e) => set('logo_url', e.target.value)}
                    />
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                      <label style={{ fontSize: 13, fontWeight: 500 }}>Primary Color</label>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        <input
                          type="color"
                          value={config.primary_color}
                          onChange={(e) => set('primary_color', e.target.value)}
                          style={{ width: 48, height: 36, cursor: 'pointer', border: 'none', borderRadius: 4 }}
                        />
                        <span style={{ fontSize: 13, color: 'var(--color-text-muted)' }}>
                          {config.primary_color}
                        </span>
                      </div>
                    </div>
                    {config.logo_url && (
                      <div>
                        <div style={{ fontSize: 13, fontWeight: 500, marginBottom: 8 }}>Logo Preview</div>
                        <img
                          src={config.logo_url}
                          alt="Logo preview"
                          style={{ maxHeight: 60, maxWidth: '100%', objectFit: 'contain' }}
                        />
                      </div>
                    )}
                  </div>
                </Card>
              )}

              {/* Actions */}
              <div style={{ display: 'flex', gap: 10, marginTop: 16 }}>
                <Button
                  loading={saveMutation.isLoading}
                  onClick={() => saveMutation.mutate(config)}
                >
                  💾 Save Configuration
                </Button>
                <Button
                  variant="secondary"
                  loading={resetMutation.isLoading}
                  onClick={() => {
                    if (window.confirm('Reset all settings to defaults?')) {
                      resetMutation.mutate();
                    }
                  }}
                >
                  Reset to Defaults
                </Button>
              </div>
            </>
          )}
        </div>

        {/* Live Preview Panel */}
        {previewVisible && (
          <Card title="Live Preview" style={{ height: 'fit-content' }}>
            <div
              style={{
                border: '2px solid var(--color-border)',
                borderRadius: 'var(--radius-md)',
                overflow: 'hidden',
              }}
            >
              {/* Simulated browser chrome */}
              <div
                style={{
                  background: '#f1f5f9',
                  padding: '6px 12px',
                  borderBottom: '1px solid var(--color-border)',
                  display: 'flex',
                  alignItems: 'center',
                  gap: 6,
                }}
              >
                {['#fc5c57', '#fdbc2c', '#25c840'].map((c) => (
                  <div key={c} style={{ width: 10, height: 10, borderRadius: '50%', background: c }} />
                ))}
                <div
                  style={{
                    flex: 1,
                    background: '#fff',
                    borderRadius: 4,
                    padding: '3px 8px',
                    fontSize: 11,
                    color: 'var(--color-text-muted)',
                    marginLeft: 8,
                  }}
                >
                  {config.site_url || 'https://example.com'}
                </div>
              </div>

              {/* Preview content */}
              <div
                style={{
                  padding: 16,
                  background: '#fff',
                  minHeight: 200,
                }}
              >
                {/* Header bar */}
                <div
                  style={{
                    background: config.primary_color,
                    color: '#fff',
                    padding: '8px 12px',
                    borderRadius: 4,
                    marginBottom: 12,
                    display: 'flex',
                    alignItems: 'center',
                    gap: 8,
                  }}
                >
                  {config.logo_url && (
                    <img src={config.logo_url} alt="logo" style={{ height: 22, objectFit: 'contain' }} />
                  )}
                  <strong style={{ fontSize: 13 }}>{config.site_name || 'Site Name'}</strong>
                </div>
                <div style={{ fontSize: 11, color: 'var(--color-text-muted)', marginBottom: 10 }}>
                  {config.site_tagline || 'Site tagline goes here'}
                </div>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                  {Object.entries(config.modules)
                    .filter(([, v]) => v)
                    .map(([k]) => (
                      <span
                        key={k}
                        style={{
                          background: `${config.primary_color}20`,
                          color: config.primary_color,
                          padding: '2px 8px',
                          borderRadius: 99,
                          fontSize: 10,
                          fontWeight: 600,
                        }}
                      >
                        {k.replace(/_/g, ' ')}
                      </span>
                    ))}
                </div>
              </div>
            </div>
          </Card>
        )}
      </div>
    </div>
  );
}
