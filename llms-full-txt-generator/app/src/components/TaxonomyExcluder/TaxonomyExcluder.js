import React, { useState, useRef, useEffect } from 'react';

const TaxonomyExcluder = ({ label, taxonomyType, postType, data, onChange }) => {
  const [input, setInput] = useState('');
  const [showDropdown, setShowDropdown] = useState(false);
  const [filtered, setFiltered] = useState([]);
  const [activeIndex, setActiveIndex] = useState(-1);

  const inputRef = useRef(null);
  const dropdownRef = useRef(null);

  const allTerms = (data.postTypeTaxonomies?.[postType]?.[taxonomyType] || [])
    .flatMap(tax => tax.terms.map(t => ({ ...t, taxLabel: tax.label })));

  const excluded = data.excludeTaxonomies?.[postType]?.[taxonomyType] || [];

  useEffect(() => {
    if (!input.trim()) {
      setFiltered([]);
      setShowDropdown(false);
      return;
    }
    const q = input.toLowerCase();
    const matches = allTerms
      .filter(t => !excluded.includes(t.name))
      .filter(t => t.name.toLowerCase().includes(q));
    setFiltered(matches);
    setShowDropdown(matches.length > 0);
    setActiveIndex(-1);
  }, [input, excluded, allTerms]);

  const addTerm = (name) => {
    const updated = [...excluded, name];
    onChange({
      ...data,
      excludeTaxonomies: {
        ...data.excludeTaxonomies,
        [postType]: {
          ...data.excludeTaxonomies?.[postType],
          [taxonomyType]: updated
        }
      }
    });
    setInput('');
    setShowDropdown(false);
  };

  const removeTerm = (name) => {
    const updated = excluded.filter(t => t !== name);
    onChange({
      ...data,
      excludeTaxonomies: {
        ...data.excludeTaxonomies,
        [postType]: {
          ...data.excludeTaxonomies?.[postType],
          [taxonomyType]: updated
        }
      }
    });
  };

  const handleKeyDown = (e) => {
    if (!showDropdown) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveIndex(p => (p + 1) % filtered.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveIndex(p => (p - 1 + filtered.length) % filtered.length);
    } else if (e.key === 'Enter' && activeIndex >= 0) {
      e.preventDefault();
      addTerm(filtered[activeIndex].name);
    }
  };

  return (
    <div className="filter-section">
      <label className="filter-label">{label}</label>
      <div className="tag-input-container" onClick={() => inputRef.current?.focus()}>
        <div className="existing-tags-categories">
          {excluded.map(term => (
            <div key={term} className="tag">
              <span>{term}</span>
              <button type="button" className="tag-remove" onClick={(e) => { e.stopPropagation(); removeTerm(term); }}>
                ×
              </button>
            </div>
          ))}
        </div>
        <div className="tag-input-wrapper">
          <input
            ref={inputRef}
            type="text"
            className="tag-input"
            value={input}
            onChange={e => setInput(e.target.value)}
            onKeyDown={handleKeyDown}
            onFocus={() => input && setShowDropdown(true)}
            placeholder="Search..."
          />
          {showDropdown && (
            <ul ref={dropdownRef} className="tag-dropdown">
              {filtered.map((term, i) => (
                <li
                  key={term.id}
                  className={`tag-dropdown-item ${i === activeIndex ? 'active' : ''}`}
                  onMouseDown={e => e.preventDefault()}
                  onClick={() => addTerm(term.name)}
                >
                  {term.name} {term.taxLabel && term.taxLabel !== 'Tags' ? `(${term.taxLabel})` : ''}
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
};

export default TaxonomyExcluder;