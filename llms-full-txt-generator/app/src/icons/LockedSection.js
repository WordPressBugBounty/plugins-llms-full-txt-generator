import React from "react";

const LockedSection = ({ children }) => {
  return (
    <div style={styles.wrapper}>
      <div style={styles.content}>{children}</div>

      <div style={styles.centerBox}>
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
          <path
            d="M6 10V8C6 4.686 8.686 2 12 2s6 2.686 6 6v2h1a1 1 0 011 1v11a1 
            1 0 01-1 1H5a1 1 0 01-1-1V11a1 1 0 011-1h1zm2 0h8V8c0-2.21-1.79-4-4-4s-4 
            1.79-4 4v2z"
            fill="#646970"
          />
        </svg>

        <div style={styles.label}>Pro Feature</div>
      </div>
    </div>
  );
};

const styles = {
  wrapper: {
    position: "relative",
    width: "100%",
    pointerEvents: "none",
  },
  content: {
    filter: "blur(1px)",
    opacity: 0.5,
  },
  centerBox: {
    position: "absolute",
    top: "50%",
    left: "50%",
    transform: "translate(-50%, -50%)",
    textAlign: "center",
    pointerEvents: "none",
  },
  label: {
    marginTop: "6px",
    color: "#502891",
    fontSize: "13px",
    fontWeight: 500,
  },
};

export default LockedSection;
