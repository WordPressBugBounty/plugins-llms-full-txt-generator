import React from 'react';
import './UpgradeBanner.css';
const { __ } = wp.i18n;

const UpgradeBanner = () => {
  const assetsUrl = window.llms_txt_generator?.assets_url || 'https://yourdomain.com/wp-content/plugins/llms-txt-generator/assets/';

  return (
    <div className="llms-upgrade-banner">
      <div
        className="llms-upgrade-inner"
        style={{
          background: `url(${assetsUrl}images/upgrade-bg.svg) bottom no-repeat, linear-gradient(180deg, #21AEFD 0.19%, #006FFF 100%)`,
          backgroundSize: 'contain',
        }}
      >
        <a
          className="llms-top-upgrade"
          href="https://acowebs.com/llms-txt-pro-for-wordpress/"
          target="_blank"
          rel="noopener noreferrer"
        >
          {__('Upgrade', 'llms-full-txt-generator')}
        </a>

        <h2>{__('Upgrade to Pro version Now!', 'llms-full-txt-generator')}</h2>
        <p>
          {__('Unlock powerful advanced features and get full control over your llms.txt and llms-full.txt files.', 'llms-full-txt-generator')}
        </p>
        <ul className="llms-pro-features-list">
          <li>Company metadata management</li>
          <li>Flexible post type selection</li>
          <li>WooCommerce product controls</li>
          <li>External URL handling & wildcards</li>
          <li>Advanced include/exclude rules</li>
          <li>Scheduled regeneration</li>
          <li>Preview & one-click update</li>
          <li>Multisite network support</li>
          <li>AI crawler analytics</li>
          <li>Role-based permissions</li>
          <li>AI usage policy settings</li>
        </ul>

        <div className="llms-upgrade-buttons">
          <a
            href="https://acowebs.com/llms-txt-pro-for-wordpress/"
            target="_blank"
            rel="noopener noreferrer"
            className="llms-upgrade-btn primary"
          >
            {__('Upgrade Now', 'llms-full-txt-generator')} →
          </a>
          <a
            href="https://acowebs.com/llms-txt-pro-for-wordpress"
            target="_blank"
            rel="noopener noreferrer"
            className="llms-upgrade-btn secondary"
          >
            {__('View All Features', 'llms-full-txt-generator')}
          </a>
        </div>
      </div>
    </div>
  );
};

export default UpgradeBanner;
