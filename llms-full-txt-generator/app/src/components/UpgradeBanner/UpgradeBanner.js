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
          <li>{__('Company Information Management – Add company name, email, description and more for inclusion in metadata', 'llms-full-txt-generator')}</li>
          <li>{__('Flexible Post Type Selection – Choose which post types to include (posts, pages, products, custom types) with specific filters', 'llms-full-txt-generator')}</li>
          <li>{__('WooCommerce Product Controls – Select what appears: name, price, SKU, attributes and more', 'llms-full-txt-generator')}</li>
          <li>{__('External URL Handling – Add specific external URLs and exclude others using wildcards (e.g., /draft-*, /private/)', 'llms-full-txt-generator')}</li>
          <li>{__('Advanced Inclusion/Exclusion – Automatically remove URLs blocked by robots.txt and use wildcard patterns', 'llms-full-txt-generator')}</li>
          <li>{__('Update Frequency Controls – Schedule automatic regeneration (daily, weekly, monthly) or trigger manually', 'llms-full-txt-generator')}</li>
          <li>{__('Preview & Regeneration – Preview content before saving and regenerate/save with one click', 'llms-full-txt-generator')}</li>
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
