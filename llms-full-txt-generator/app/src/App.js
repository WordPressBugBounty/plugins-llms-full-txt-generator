import React, { useState, useEffect } from 'react';
import axios from 'axios';
import GenerateTab from './pages/GenerateTab/GenerateTab';
import SettingTab from './pages/SettingTab/SettingTab';
import './App.css';
import BarLoader from './components/Loader/Loader';
import FilterIcon from './icons/FilterIcon';
import NavTabIcon from './icons/NavTabIcon';

const STORAGE_KEY = 'GenerateTab__filesToGenerate';

const App = () => {
  const [tab, setTab] = useState('generate');
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [generating, setGenerating] = useState(false);
  const [msg, setMsg] = useState({ text: '', type: '' });
  const [sublistOpen, setSublistOpen] = useState({ product: false, post: false });



  const restUrl = window.llmsData.restUrl;
  const nonce = window.llmsData.nonce;

  const fetchSettings = async () => {
    try {
      const res = await axios.get(`${restUrl}/settings`, {
        headers: { 'X-WP-Nonce': nonce }
      });

      const incoming = res.data;

      // Default values if backend doesn't send them
      const defaultFilesToGenerate = [
        { value: 'llms.txt', checked: true },
        { value: 'llms-full.txt', checked: true }
      ];

      const defaultProduct = {
        showPrice: false,
        showCategories: false,
        showTags: false,
        showRatings: false,
        showUrl: false,
        showImageUrl: false,
        excludeCategories: [],
        excludeTags: [],
        allCategories: [],
        allTags: []
      };

      const defaultPost = {
        showCategories: false,
        showTags: false,
        excludeCategories: [],
        excludeTags: [],
        allCategories: [],
        allTags: []
      };

      // Load from localStorage (user's last choice)
      let savedFiles = null;
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        try {
          savedFiles = JSON.parse(stored);
        } catch (e) {
          console.warn('Corrupted filesToGenerate in localStorage, clearing...');
          localStorage.removeItem(STORAGE_KEY);
        }
      }

      // Final merged data
      const mergedData = {
        ...incoming,

        // Critical: Always ensure these exist
        filesToGenerate: savedFiles || incoming.filesToGenerate || defaultFilesToGenerate,

        product: incoming.product ? { ...defaultProduct, ...incoming.product } : defaultProduct,
        post: incoming.post ? { ...defaultPost, ...incoming.post } : defaultPost,

        // Ensure other important fields exist
        includeUrls: incoming.includeUrls || '',
        excludeUrls: incoming.excludeUrls || '',
        companyName: incoming.companyName || '',
        companyDescription: incoming.companyDescription || '',
        adminEmail: incoming.adminEmail || '',
        updateFrequency: incoming.updateFrequency || 'manual',
        respectSeo: incoming.respectSeo !== undefined ? incoming.respectSeo : true,
        multilingual: incoming.multilingual || false,
      };

      setData(mergedData);
    } catch (e) {
      console.error('Failed to load settings:', e);
      setMsg({ text: 'Failed to load settings. Please refresh.', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSettings();
  }, []);

 

 

  const save = async () => {
    setSaving(true);
    try {
      await axios.post(`${restUrl}/settings`, data, {
        headers: { 'X-WP-Nonce': nonce }
      });
      setMsg({ text: 'Settings saved successfully!', type: 'success' });
    } catch (e) {
      setMsg({ text: 'Failed to save settings.', type: 'error' });
    } finally {
      setSaving(false);
      setTimeout(() => setMsg({ text: '', type: '' }), 3000);
    }
  };

  const generate = async () => {
    const files = (data.filesToGenerate || [])
      .filter(f => f.checked)
      .map(f => f.value);

    if (!files.length) {
      setMsg({ text: 'Please select at least one file to generate.', type: 'error' });
      return;
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify(data.filesToGenerate));

    setGenerating(true);
    setMsg({ text: '', type: '' });

    try {
      const res = await axios.post(
        `${restUrl}/generate`,
        { files },
        { headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' } }
      );
      setMsg({ text: `Generated: ${res.data.files.join(', ')}`, type: 'success' });
      fetchSettings(); // Refresh URLs
    } catch (error) {
      const txt = error.response?.data?.message || error.message || 'Unknown error';
      setMsg({ text: `Generation failed: ${txt}`, type: 'error' });
    } finally {
      setGenerating(false);
      setTimeout(() => setMsg({ text: '', type: '' }), 4000);
    }
  };

  const deleteFile = async (file) => {
    if (!confirm(`Delete ${file}?`)) return;
    try {
      await axios.post(`${restUrl}/delete/${file}`, {}, {
        headers: { 'X-WP-Nonce': nonce }
      });
      setMsg({ text: `${file} deleted`, type: 'success' });
      fetchSettings();
    } catch {
      setMsg({ text: 'Delete failed.', type: 'error' });
    }
  };

 

  if (loading) return <div className="loader"><BarLoader /></div>;

  return (
    <div className="wrap">
      <h1 className='main-heading-container'>LLMS Full TXT Generator</h1>

      {msg.text && (
        <div className={`notice notice-${msg.type}`}>
          <p>{msg.text}</p>
        </div>
      )}

      <nav className="nav-tab-wrapper">
        <a href="#" className={`nav-tab ${tab === 'generate' ? 'nav-tab-active' : ''}`} onClick={(e) => { e.preventDefault(); setTab('generate'); }}>
          <div className='nav-tab-sub-div'>
            <div className='nav-tab-svg'>
              <NavTabIcon />
            </div>
            <div className='nav-tab-text'>Generate</div>
          </div>
        </a>
        <a href="#" className={`nav-tab ${tab === 'settings' ? 'nav-tab-active' : ''}`} onClick={(e) => { e.preventDefault(); setTab('settings'); }}>
          <div className='nav-tab-sub-div'>
            <div className='nav-tab-svg'>
              <FilterIcon />
            </div>
            <div className='nav-tab-text'>Settings</div>
          </div>
        </a>
      </nav>

      <div className="tab-content">
        {tab === 'generate' ? (
          <GenerateTab
            data={data}
            setData={setData}
            generating={generating}
            onGenerate={generate}
            onDelete={deleteFile}
          />
        ) : (
          <SettingTab
            data={data}
            saving={saving}
            sublistOpen={sublistOpen}
            onToggleSublist={(key) => setSublistOpen(s => ({ ...s, [key]: !s[key] }))}
            onChange={setData}
            onSave={save}
          />
        )}
      </div>
    </div>
  );
};

export default App;