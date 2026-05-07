// VisibilityAds.js
import React, { useEffect, useState } from 'react';
import VisibilityAdsExit from './VisibilityAdsExit/VisibilityAdsExit';
import './VisibilityAds.css';

const HIDE_UNTIL_KEY = 'visibility_ads_hide_until';
const HIDE_ALWAYS_KEY = 'visibility_ads_hide_always';
const ONE_DAY_MS = 24 * 60 * 60 * 1000;

const readStorageItem = (key) => {
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
};

const writeStorageItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch {
    // Ignore storage failures so dismissal still works.
  }
};

const removeStorageItem = (key) => {
  try {
    localStorage.removeItem(key);
  } catch {
    // Ignore storage failures so dismissal still works.
  }
};

const getInitialHiddenState = () => {
  const hideAlways = readStorageItem(HIDE_ALWAYS_KEY) === 'true';
  const hideUntilValue = Number(readStorageItem(HIDE_UNTIL_KEY) || 0);
  const hideUntil = Number.isFinite(hideUntilValue) ? hideUntilValue : 0;

  return hideAlways || (hideUntil && hideUntil > Date.now());
};

const VisibilityAds = () => {
  const [isHidden, setIsHidden] = useState(() => getInitialHiddenState());
  const [isPopupOpen, setIsPopupOpen] = useState(false);

  useEffect(() => {
    const hideAlways = readStorageItem(HIDE_ALWAYS_KEY) === 'true';
    const hideUntilValue = Number(readStorageItem(HIDE_UNTIL_KEY) || 0);
    const hideUntil = Number.isFinite(hideUntilValue) ? hideUntilValue : 0;

    if (hideAlways || (hideUntil && hideUntil > Date.now())) {
      setIsHidden(true);
      return;
    }

    if (hideUntil && hideUntil <= Date.now()) {
      removeStorageItem(HIDE_UNTIL_KEY);
    }
  }, []);

  const hideForOneDay = () => {
    setIsPopupOpen(false);
    setIsHidden(true);
    writeStorageItem(HIDE_UNTIL_KEY, String(Date.now() + ONE_DAY_MS));
    removeStorageItem(HIDE_ALWAYS_KEY);
  };

  const hideForever = () => {
    setIsPopupOpen(false);
    setIsHidden(true);
    writeStorageItem(HIDE_ALWAYS_KEY, 'true');
    removeStorageItem(HIDE_UNTIL_KEY);
  };

  if (isHidden) {
    return null;
  }

  return (
    <section className="visibility-ads-shell">
      <VisibilityAdsExit
        isPopupOpen={isPopupOpen}
        onOpenPopup={() => setIsPopupOpen(true)}
        onClosePopup={() => setIsPopupOpen(false)}
        onTryLater={hideForOneDay}
        onHideAlways={hideForever}
      />
      <iframe
        src="https://promo.doable.team/frame/wordpress%20llm"
        title="Visibility Promo"
        style={{
          border: 0,
          width: '100%',
          height: '90px',
          display: 'block',
        }}
      />
    </section>
  );
};

export default VisibilityAds;