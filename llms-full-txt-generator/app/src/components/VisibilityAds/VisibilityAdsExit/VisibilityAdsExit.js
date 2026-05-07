import React, { useEffect, useRef } from 'react';
import './VisibilityAdsExit.css';
import ExitIcon from '../../../icons/ExitIcon';

const VisibilityAdsExit = ({ isPopupOpen, onOpenPopup, onClosePopup, onTryLater, onHideAlways }) => {
  const tryLaterButtonRef = useRef(null);

  useEffect(() => {
    if (isPopupOpen) {
      tryLaterButtonRef.current?.focus();
    }
  }, [isPopupOpen]);

  return (
    <>
      <button
        type="button"
        className="visibility-ads-exit-button"
        onClick={onOpenPopup}
        aria-label="Open banner options"
      >
       <ExitIcon size={13} color="#505C6D" />
      </button>

      {isPopupOpen && (
        <div className="visibility-ads-popup" role="dialog" aria-modal="true" aria-label="Visibility banner options">
          <button
            type="button"
            className="visibility-ads-popup__backdrop"
            onClick={onClosePopup}
            aria-label="Close banner options"
          />

          <div className="visibility-ads-popup__panel">
            <h3>See this offer later?</h3>
            <p>Choose when to show this promotion again.</p>

            <div className="visibility-ads-popup__actions">
              <button
                type="button"
                ref={tryLaterButtonRef}
                className="visibility-ads-popup__button visibility-ads-popup__button--primary"
                onClick={onTryLater}
              >
                Try later
              </button>
              <button
                type="button"
                className="visibility-ads-popup__button visibility-ads-popup__button--secondary"
                onClick={onHideAlways}
              >
                Hide always
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default VisibilityAdsExit;