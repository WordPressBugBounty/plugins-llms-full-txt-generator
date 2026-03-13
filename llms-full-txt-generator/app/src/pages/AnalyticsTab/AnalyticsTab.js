// AnalyticsTabStatic.jsx
import React, { useState, useEffect } from 'react';
import {
  TrendingUp,
  ChevronDown,
  Bot,
  ExternalLink,
  Search,
} from 'lucide-react';

import './AnalyticsTab.css'; // ← your provided CSS
import LockedSection from '../../icons/LockedSection';

// Mock data ────────────────────────────────────────────────────────────────
const mockAnalytics = {
  totalVisits: 1247,
  todayVisits: 38,
  weekVisits: 312,
  visitsByBot: [
    { bot_name: 'GPTBot', count: 518 },
    { bot_name: 'Google-Extended', count: 341 },
    { bot_name: 'anthropic-ai', count: 187 },
    { bot_name: 'PerplexityBot', count: 124 },
    { bot_name: 'Bytespider (ByteDance)', count: 47 },
    { bot_name: 'ClaudeBot', count: 30 },
  ],
  recentVisits: [
    {
      bot_name: 'GPTBot',
      url: '/llms.txt',
      visited_at: '2025-03-09T14:22:19Z',
    },
    {
      bot_name: 'Google-Extended',
      url: '/blog/ai-crawlers-2025',
      visited_at: '2025-03-09T11:47:03Z',
    },
    {
      bot_name: 'anthropic-ai',
      url: '/products/premium-access',
      visited_at: '2025-03-09T09:15:44Z',
    },
    {
      bot_name: 'PerplexityBot',
      url: '/category/wordpress',
      visited_at: '2025-03-09T06:38:12Z',
    },
    {
      bot_name: 'GPTBot',
      url: '/contact',
      visited_at: '2025-03-09T04:19:55Z',
    },

  ]
};

const getBotColor = (name) => {
  const lower = name.toLowerCase();
  if (lower.includes('gemini') || lower.includes('google')) return '#2dd4bf';
  if (lower.includes('claude') || lower.includes('anthropic')) return '#10b981';
  if (lower.includes('perplexity')) return '#60a5fa';
  if (lower.includes('gpt') || lower.includes('openai')) return '#f59e0b';
  return '#722ed1';
};

const AnalyticsTabStatic = () => {
  const [analytics] = useState(mockAnalytics);
  const [selectedDays] = useState(30); // decorative – no real filtering here
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 15;

  // Reset page when search changes
  useEffect(() => {
    setCurrentPage(1);
  }, [searchTerm]);

  const bots = analytics.visitsByBot || [];
  const recent = analytics.recentVisits || [];
  const total = analytics.totalVisits || 0;

  const filteredRecent = recent.filter(
    (item) =>
      (item.bot_name || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
      (item.url || '').toLowerCase().includes(searchTerm.toLowerCase()),
  );

  const totalPages = Math.ceil(filteredRecent.length / itemsPerPage);
  const paginatedRecent = filteredRecent.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage,
  );

  // Fake home URL for display (in real version comes from window.llmsData)
  const homeUrl = 'https://your-wordpress-site.com';

  return (
    <div className="analytics-tab-wrapper">
      {/* You can add fake message here if you want */}
      {/* <div className="notice notice-success">
        <p>Sample message (static version)</p>
      </div> */}

      <LockedSection>
        <div className="analytics-header">
        <h2>AI Bot Analytics</h2>
        <div className="controls-group">
          <div className="select-wrapper">
            <select value={selectedDays} disabled>
              <option value={7}>Last 7 days</option>
              <option value={30}>Last 30 days</option>
              <option value={60}>Last 60 days</option>
              <option value={90}>Last 90 days</option>
            </select>
            <ChevronDown size={14} className="select-icon" />
          </div>

          <button className="btn-clear" disabled>
            Clear Old Data
          </button>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="stats-grid">
        <div className="stat-card purple">
          <div className="stat-top">
            <div className="stat-label">Total Visits</div>
            <div className="stat-icon-bg purple" />
          </div>
          <div className="stat-value">{total.toLocaleString()}</div>
          <div className="stat-footer">
            <div className="trend-badge">
              <TrendingUp size={10} /> Active
            </div>
            <span>Last {selectedDays} days</span>
          </div>
        </div>

        <div className="stat-card blue">
          <div className="stat-top">
            <div className="stat-label">Today's Visits</div>
            <div className="stat-icon-bg blue" />
          </div>
          <div className="stat-value">{analytics.todayVisits}</div>
          <div className="stat-footer">
            <span>Since midnight</span>
          </div>
        </div>

        <div className="stat-card emerald">
          <div className="stat-top">
            <div className="stat-label">This Week</div>
            <div className="stat-icon-bg emerald" />
          </div>
          <div className="stat-value">{analytics.weekVisits}</div>
          <div className="stat-footer">
            <span>Last 7 days</span>
          </div>
        </div>
      </div>

      {/* Visits by Bot */}
      <div className="section-card">
        <h3>Visits by AI Bot</h3>
        <div className="bot-card-grid">
          {bots.length > 0 ? (
            bots.map((bot, i) => (
              <div key={i} className="bot-visit-card">
                <div className="bot-card-header">
                  <span className="bot-card-name">{bot.bot_name}</span>
                </div>
                <div className="bot-card-count">{bot.count} visits</div>
                <div className="bot-card-bar-bg">
                  <div
                    className="bot-card-bar"
                    style={{
                      width: `${(bot.count / (analytics.totalVisits || 1)) * 100}%`,
                      backgroundColor: getBotColor(bot.bot_name),
                    }}
                  />
                </div>
              </div>
            ))
          ) : (
            <p className="no-data-text">No AI crawler visits detected in this period.</p>
          )}
        </div>
      </div>

      {/* Recent Visits */}
      <div className="section-card table-section">
        <div className="table-header">
          <h3>
            Recent Visits {filteredRecent.length > 0 && `(${filteredRecent.length})`}
          </h3>

          <div className="search-wrapper">
            <div className="search-input-container">
              <Search size={18} className="search-icon" />
              <input
                type="text"
                placeholder="Search by bot name or URL..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="table-search-input"
              />
            </div>

            {searchTerm && (
              <button
                className="clear-search-btn"
                onClick={() => setSearchTerm('')}
                aria-label="Clear search"
              >
                ✕
              </button>
            )}
          </div>
        </div>

        {filteredRecent.length > 0 ? (
          <>
            <div className="table-container">
              <table>
                <thead>
                  <tr>
                    <th>Bot Name</th>
                    <th>URL</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody>
                  {paginatedRecent.map((v, i) => {
                    const fullUrl = v.url.startsWith('http')
                      ? v.url
                      : homeUrl.replace(/\/$/, '') + v.url;

                    let displayUrl = '';
                    try {
                      const urlObj = new URL(homeUrl);
                      displayUrl = urlObj.host + v.url;
                    } catch {
                      displayUrl = homeUrl.replace(/^https?:\/\//, '').split('/')[0] + v.url;
                    }

                    return (
                      <tr key={i} className="hover-row">
                        <td>
                          <div className="bot-cell">
                            <Bot size={14} className="bot-icon" />
                            {v.bot_name}
                          </div>
                        </td>
                        <td className="url-cell">
                          <a
                            href={fullUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            title={fullUrl}
                          >
                            {displayUrl}
                            <ExternalLink size={12} className="external-icon" />
                          </a>
                        </td>
                        <td className="time-cell">
                          {new Date(v.visited_at).toLocaleString()}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>

            {totalPages > 1 && (
              <div className="pagination">
                <span className="pagination-info">
                  Showing{' '}
                  {(currentPage - 1) * itemsPerPage + 1}–
                  {Math.min(currentPage * itemsPerPage, filteredRecent.length)} of{' '}
                  {filteredRecent.length}
                </span>
                <div className="pagination-container">
                  <button
                    onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                    disabled={currentPage === 1}
                    className="page-btn"
                  >
                    ← Previous
                  </button>

                  <div className="page-numbers">
                    {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                      <button
                        key={page}
                        onClick={() => setCurrentPage(page)}
                        className={`page-btn ${currentPage === page ? 'active' : ''}`}
                      >
                        {page}
                      </button>
                    ))}
                  </div>

                  <button
                    onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                    disabled={currentPage === totalPages}
                    className="page-btn"
                  >
                    Next →
                  </button>
                </div>
              </div>
            )}
          </>
        ) : (
          <p className="no-data-text">
            {searchTerm ? 'No matching visits found.' : 'No recent visits to display.'}
          </p>
        )}
      </div>
      </LockedSection>
    </div>
  );
};

export default AnalyticsTabStatic;