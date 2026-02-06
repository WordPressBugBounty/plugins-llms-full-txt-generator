import React from 'react';
import './ToggleSwitch.css';

const ToggleSwitch = ({ checked, onChange, label, id }) => {
  return (
    <label className="toggle-switch" htmlFor={id}>
      <input
        id={id}
        type="checkbox"
        checked={checked}
        onChange={onChange}
      />
      <span className="slider"></span>
      {label && <span className="toggle-label">{label}</span>}
    </label>
  );
};

export default ToggleSwitch;