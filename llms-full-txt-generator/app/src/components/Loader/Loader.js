

import React, { useEffect } from 'react';

const CSS_ID = 'barloader-styles';

export default function BarLoader({
  width = '30%',
  height = 8,
  color = '#502891', // primary bar color
  background = '#EDE6FF', // track background
  borderRadius = 6,
  duration = 1.6, // seconds for shimmer loop
  ariaLabel = 'Loading',
}) {
  useEffect(() => {
    if (document.getElementById(CSS_ID)) return;

    const css = `
      .barloader {
        display: block;
        width: var(--bar-width);
        background: var(--bar-bg);
        border-radius: var(--bar-radius);
        overflow: hidden;
        position: relative;
        height: var(--bar-height);
      }

      /* moving shimmer element */
      .barloader__shimmer {
        position: absolute;
        top: 0;
        left: -40%;
        width: 40%;
        height: 100%;
        transform: skewX(-20deg);
        background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.5) 50%, transparent 100%);
        animation: barloader-shimmer var(--bar-duration) linear infinite;
      }

      /* primary fill that shows color (subtle growth animation) */
      .barloader__fill {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 30%; /* starting visual width, not an actual progress value */
        background: var(--bar-color);
        border-radius: inherit;
        transform-origin: left center;
        animation: barloader-pulse calc(var(--bar-duration) * 2) ease-in-out infinite;
      }

      @keyframes barloader-shimmer {
        0% { left: -40%; }
        100% { left: 140%; }
      }

      @keyframes barloader-pulse {
        0% { width: 20%; }
        50% { width: 72%; }
        100% { width: 20%; }
      }

      /* reduced motion preference */
      @media (prefers-reduced-motion: reduce) {
        .barloader__shimmer { animation: none; }
        .barloader__fill { animation: none; }
      }

      /* small helper for a thin variant */
      .barloader--thin { height: calc(var(--bar-height) * 0.6); }
    `;

    const style = document.createElement('style');
    style.id = CSS_ID;
    style.textContent = css;
    document.head.appendChild(style);
  }, []);

  const styleVars = {
    '--bar-width': typeof width === 'number' ? `${width}px` : width,
    '--bar-height': typeof height === 'number' ? `${height}px` : height,
    '--bar-color': color,
    '--bar-bg': background,
    '--bar-radius': typeof borderRadius === 'number' ? `${borderRadius}px` : borderRadius,
    '--bar-duration': `${duration}s`,
  };

  return (
    <div
      className="barloader"
      role="progressbar"
      aria-busy="true"
      aria-label={ariaLabel}
      style={styleVars}
    >
      <div className="barloader__fill" />
      <div className="barloader__shimmer" />
    </div>
  );
}

/* Notes:
 - This is a decorative indeterminate loader (not tied to an actual numeric progress value).
 - To display a determinate progress use a controlled width on `.barloader__fill` via style or a prop.
 - Example determinate usage: <div className="barloader__fill" style={{ width: `${progress}%` }} />
 - The component injects CSS into document.head automatically (keeps the example single-file).
*/
