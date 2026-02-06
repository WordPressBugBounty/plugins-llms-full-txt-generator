import './SettingTab.css';
import React, { useState, useRef, useEffect } from 'react';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import ToggleSwitch from '../../components/ToggleSwitch/ToggleSwitch';
import TrashIcon from '../../icons/TrashIcon';
import LockedSection from '../../icons/LockedSection';

// ---------------------------------------------------------------------
// Sortable Post Type Item
// ---------------------------------------------------------------------
function SortablePostTypeItem({
  pt,
  data,
  togglePostType,
  onChange,
  sublistOpen,
  onToggleSublist,
}) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: pt.name });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.6 : 1,
  };

  return (
    <div ref={setNodeRef} style={style} className="post-type-item">
      <span
        className="dashicons dashicons-menu drag-handle"
        {...attributes}
        {...listeners}
        style={{ cursor: isDragging ? 'grabbing' : 'grab' }}
      />
      <input
        type="checkbox"
        id={`pt-${pt.name}`}
        checked={pt.selected}
        onChange={(e) => onChange(togglePostType(pt.name, e.target.checked))}
      />
      <label htmlFor={`pt-${pt.name}`} className="post-type-label">
        <strong>{pt.label}</strong>
      </label>
      {(pt.name === 'product' || pt.name === 'post') && (
        <div
          className="dashicons-arrow-container"
          onClick={() => onToggleSublist(pt.name)}
        >
          <span
            className={`dashicons dashicons-arrow-${sublistOpen[pt.name] ? 'up' : 'down'} toggle-sublist`}
          />
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------
// Main SettingTab Component
// ---------------------------------------------------------------------
const SettingTab = ({ data, saving, sublistOpen, onToggleSublist, onChange, onSave }) => {
  const sectionsRef = useRef({});
  const formContainerRef = useRef(null);

  const [leftOpen, setLeftOpen] = useState(true);
  const [activeSection, setActiveSection] = useState('company');
  const [includeUrlInput, setIncludeUrlInput] = useState('');
  const setField = (field, value) => onChange({ ...data, [field]: value });

  /* ---------- Product Tag Input State ---------- */
  const [excludeCatInput, setExcludeCatInput] = useState('');
  const [excludeTagInput, setExcludeTagInput] = useState('');
  const [showCatDropdown, setShowCatDropdown] = useState(false);
  const [showTagDropdown, setShowTagDropdown] = useState(false);
  const [filteredCats, setFilteredCats] = useState([]);
  const [filteredTags, setFilteredTags] = useState([]);
  const [activeIndex, setActiveIndex] = useState(-1);

  const catInputRef = useRef(null);
  const tagInputRef = useRef(null);
  const catDropdownRef = useRef(null);
  const tagDropdownRef = useRef(null);

  /* ---------- Post Tag Input State (DEDICATED) ---------- */
  const [excludePostCatInput, setExcludePostCatInput] = useState('');
  const [excludePostTagInput, setExcludePostTagInput] = useState('');
  const [showPostCatDropdown, setShowPostCatDropdown] = useState(false);
  const [showPostTagDropdown, setShowPostTagDropdown] = useState(false);
  const [filteredPostCats, setFilteredPostCats] = useState([]);
  const [filteredPostTags, setFilteredPostTags] = useState([]);
  const [activePostIndex, setActivePostIndex] = useState(-1);

  const postCatInputRef = useRef(null);
  const postTagInputRef = useRef(null);
  const postCatDropdownRef = useRef(null);
  const postTagDropdownRef = useRef(null);

  /* ---------- DND Sensors ---------- */
  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  /* ---------- Drag End Handler ---------- */
  const handleDragEnd = (event) => {
    const { active, over } = event;
    if (!over || active.id === over.id) return;

    const oldIndex = data.postTypes.findIndex((pt) => pt.name === active.id);
    const newIndex = data.postTypes.findIndex((pt) => pt.name === over.id);

    const newOrder = arrayMove(data.postTypes, oldIndex, newIndex);
    onChange({ ...data, postTypes: newOrder });
  };

  /* ---------- Close Dropdowns on Click Outside ---------- */
  useEffect(() => {
    const handleClickOutside = (e) => {
      // Product dropdowns
      if (catDropdownRef.current && !catDropdownRef.current.contains(e.target) && catInputRef.current !== e.target) {
        setShowCatDropdown(false);
      }
      if (tagDropdownRef.current && !tagDropdownRef.current.contains(e.target) && tagInputRef.current !== e.target) {
        setShowTagDropdown(false);
      }
      // Post dropdowns
      if (postCatDropdownRef.current && !postCatDropdownRef.current.contains(e.target) && postCatInputRef.current !== e.target) {
        setShowPostCatDropdown(false);
      }
      if (postTagDropdownRef.current && !postTagDropdownRef.current.contains(e.target) && postTagInputRef.current !== e.target) {
        setShowPostTagDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  /* ---------- Product Category Search ---------- */
  useEffect(() => {
    if (!excludeCatInput.trim()) {
      setFilteredCats([]);
      setShowCatDropdown(false);
      return;
    }
    const query = excludeCatInput.toLowerCase();
    const available = (data.product?.allCategories || [])
      .filter((cat) => !(data.product?.excludeCategories || []).includes(cat.name))
      .filter((cat) => cat.name.toLowerCase().includes(query));
    setFilteredCats(available);
    setShowCatDropdown(available.length > 0);
    setActiveIndex(-1);
  }, [excludeCatInput, data.product?.allCategories, data.product?.excludeCategories]);

  /* ---------- Product Tag Search ---------- */
  useEffect(() => {
    if (!excludeTagInput.trim()) {
      setFilteredTags([]);
      setShowTagDropdown(false);
      return;
    }
    const query = excludeTagInput.toLowerCase();
    const available = (data.product?.allTags || [])
      .filter((tag) => !(data.product?.excludeTags || []).includes(tag.name))
      .filter((tag) => tag.name.toLowerCase().includes(query));
    setFilteredTags(available);
    setShowTagDropdown(available.length > 0);
    setActiveIndex(-1);
  }, [excludeTagInput, data.product?.allTags, data.product?.excludeTags]);

  /* ---------- Post Category Search ---------- */
  useEffect(() => {
    if (!excludePostCatInput.trim()) {
      setFilteredPostCats([]);
      setShowPostCatDropdown(false);
      return;
    }
    const query = excludePostCatInput.toLowerCase();
    const available = (data.post?.allCategories || [])
      .filter((cat) => !(data.post?.excludeCategories || []).includes(cat.name))
      .filter((cat) => cat.name.toLowerCase().includes(query));
    setFilteredPostCats(available);
    setShowPostCatDropdown(available.length > 0);
    setActivePostIndex(-1);
  }, [excludePostCatInput, data.post?.allCategories, data.post?.excludeCategories]);

  /* ---------- Post Tag Search ---------- */
  useEffect(() => {
    if (!excludePostTagInput.trim()) {
      setFilteredPostTags([]);
      setShowPostTagDropdown(false);
      return;
    }
    const query = excludePostTagInput.toLowerCase();
    const available = (data.post?.allTags || [])
      .filter((tag) => !(data.post?.excludeTags || []).includes(tag.name))
      .filter((tag) => tag.name.toLowerCase().includes(query));
    setFilteredPostTags(available);
    setShowPostTagDropdown(available.length > 0);
    setActivePostIndex(-1);
  }, [excludePostTagInput, data.post?.allTags, data.post?.excludeTags]);

  /* ---------- Helper: Nested Update (product & post) ---------- */
  const toggleNested = (postType, key, value) => {
    onChange({
      ...data,
      [postType]: {
        ...(data[postType] || {}),
        [key]: value,
      },
    });
  };

  const toggleProduct = (key, value) => {
    toggleNested('product', key, value);
  };

  /* ---------- Product Handlers ---------- */
  const addExcludeCategory = (catName) => {
    if (!(data.product?.excludeCategories || []).includes(catName)) {
      toggleProduct('excludeCategories', [...(data.product?.excludeCategories || []), catName]);
    }
    setExcludeCatInput('');
    setShowCatDropdown(false);
  };

  const removeExcludeCategory = (cat) => {
    toggleProduct('excludeCategories', (data.product?.excludeCategories || []).filter(c => c !== cat));
  };

  const addExcludeTag = (tagName) => {
    if (!(data.product?.excludeTags || []).includes(tagName)) {
      toggleProduct('excludeTags', [...(data.product?.excludeTags || []), tagName]);
    }
    setExcludeTagInput('');
    setShowTagDropdown(false);
  };

  const removeExcludeTag = (tag) => {
    toggleProduct('excludeTags', (data.product?.excludeTags || []).filter(t => t !== tag));
  };

  const handleCatKeyDown = (e) => {
    if (!showCatDropdown) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveIndex((prev) => (prev + 1) % filteredCats.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveIndex((prev) => (prev - 1 + filteredCats.length) % filteredCats.length);
    } else if (e.key === 'Enter' && activeIndex >= 0) {
      e.preventDefault();
      addExcludeCategory(filteredCats[activeIndex].name);
    }
  };

  const handleTagKeyDown = (e) => {
    if (!showTagDropdown) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveIndex((prev) => (prev + 1) % filteredTags.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveIndex((prev) => (prev - 1 + filteredTags.length) % filteredTags.length);
    } else if (e.key === 'Enter' && activeIndex >= 0) {
      e.preventDefault();
      addExcludeTag(filteredTags[activeIndex].name);
    }
  };

  /* ---------- Post Handlers ---------- */
  const addExcludePostCategory = (catName) => {
    if (!(data.post?.excludeCategories || []).includes(catName)) {
      toggleNested('post', 'excludeCategories', [...(data.post?.excludeCategories || []), catName]);
    }
    setExcludePostCatInput('');
    setShowPostCatDropdown(false);
  };

  const removeExcludePostCategory = (cat) => {
    toggleNested('post', 'excludeCategories', (data.post?.excludeCategories || []).filter(c => c !== cat));
  };

  const addExcludePostTag = (tagName) => {
    if (!(data.post?.excludeTags || []).includes(tagName)) {
      toggleNested('post', 'excludeTags', [...(data.post?.excludeTags || []), tagName]);
    }
    setExcludePostTagInput('');
    setShowPostTagDropdown(false);
  };

  const removeExcludePostTag = (tag) => {
    toggleNested('post', 'excludeTags', (data.post?.excludeTags || []).filter(t => t !== tag));
  };

  const handlePostCatKeyDown = (e) => {
    if (!showPostCatDropdown) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActivePostIndex((prev) => (prev + 1) % filteredPostCats.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActivePostIndex((prev) => (prev - 1 + filteredPostCats.length) % filteredPostCats.length);
    } else if (e.key === 'Enter' && activePostIndex >= 0) {
      e.preventDefault();
      addExcludePostCategory(filteredPostCats[activePostIndex].name);
    }
  };

  const handlePostTagKeyDown = (e) => {
    if (!showPostTagDropdown) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActivePostIndex((prev) => (prev + 1) % filteredPostTags.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActivePostIndex((prev) => (prev - 1 + filteredPostTags.length) % filteredPostTags.length);
    } else if (e.key === 'Enter' && activePostIndex >= 0) {
      e.preventDefault();
      addExcludePostTag(filteredPostTags[activePostIndex].name);
    }
  };

  const togglePostType = (name, checked) => {
    const updated = data.postTypes.map((pt) =>
      pt.name === name ? { ...pt, selected: checked } : pt
    );
    return { ...data, postTypes: updated };
  };

  const urlList = data.includeUrls
    ? data.includeUrls.split('\n').map((s) => s.trim()).filter((s) => s)
    : [];

  const addUrl = () => {
    const clean = includeUrlInput.trim();
    if (!clean) return;
    const newValue = data.includeUrls ? `${data.includeUrls}\n${clean}` : clean;
    setField('includeUrls', newValue);
    setIncludeUrlInput('');
  };

  const removeUrl = (index) => {
    const newList = urlList.filter((_, i) => i !== index);
    setField('includeUrls', newList.join('\n'));
  };

  const sections = [
    { key: 'company', label: 'Company Information' },
    { key: 'postTypes', label: 'Post Types' },
    { key: 'includeUrls', label: 'Include URL' },
    { key: 'excludeUrls', label: 'Exclude URL' },
    { key: 'updateFrequency', label: 'Update Frequency' },
    { key: 'seo', label: 'SEO Settings' },
    { key: 'multilingual', label: 'Multilingual Support' },
  ];

  const scrollToSection = (key) => {
    const container = formContainerRef.current;
    const el = sectionsRef.current[key];
    if (container && el) {
      const top = el.offsetTop - 150;
      container.scrollTo({ top, behavior: 'smooth' });
      setActiveSection(key);
      if (window.innerWidth < 768) setLeftOpen(false);
    }
  };

  const productToggleItems = [
    { id: 'showPrice', label: 'Show Product Prices' },
    { id: 'showCategories', label: 'Show Categories' },
    { id: 'showTags', label: 'Show Tags' },
    { id: 'showRatings', label: 'Show Product Ratings' },
    { id: 'showUrl', label: 'Show Product URL' },
    { id: 'showImageUrl', label: 'Show Product Image URL' },
  ];

  return (
    <form onSubmit={(e) => { e.preventDefault(); onSave(); }} className="setting-tab">
      {/* LEFT SIDEBAR */}
      <aside className={`setting-tab-container ${leftOpen ? 'open' : 'closed'}`}>
        <div className="left-top">
          <button
            type="button"
            className="mobile-toggle"
            onClick={() => setLeftOpen((prev) => !prev)}
            aria-label="Toggle sidebar"
          />
          <div className="setting-tab-header-title">
            {sections.map((s) => (
              <div
                key={s.key}
                className={`setting-tab-header-div ${activeSection === s.key ? 'active' : ''}`}
                onClick={() => scrollToSection(s.key)}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => e.key === 'Enter' && scrollToSection(s.key)}
              >
                <h3 className="setting-tab-title">{s.label}</h3>
              </div>
            ))}
          </div>
        </div>

        <div className="setting-tab-container-button">
          <button type="submit" disabled={saving}>
            {saving ? 'Saving...' : 'Save Settings'}
          </button>
        </div>
      </aside>

      {/* RIGHT FORM AREA */}
      <div className="setting-tab-form-container" ref={formContainerRef}>
        {/* COMPANY SECTION */}
        <div className="section-wrapper" ref={(el) => (sectionsRef.current['company'] = el)}>
          <div className="settings-tab-containers">
            <h3 className='llms-heading'>Company Information</h3>
            <div className="company-name-email-container">
              <div className="company-email-div">
                <label className="company-name-label">Company Email</label>
                <input
                  type="email"
                  className="regular-text"
                  value={data.adminEmail ?? ''}
                  onChange={(e) => setField('adminEmail', e.target.value)}
                />
                <div className="email-name-label-note" ><span> <strong>Note: </strong> Leave this field empty if you don’t want the email to be displayed.
                  </span>
                </div>
              </div>
            </div>
            <LockedSection>
              <div className="company-name-div">
                <label className="company-name-label">Company Name</label>
                <input
                  type="text"
                  className="regular-text"
                  value={data.companyName ?? ''}
                  onChange={(e) => setField('companyName', e.target.value)}
                />
              </div>
              <div className="company-description-container">
                <label className="company-name-label">Company Description</label>
                <textarea
                  className="regular-text"
                  rows={4}
                  value={data.companyDescription ?? ''}
                  onChange={(e) => setField('companyDescription', e.target.value)}
                />
              </div>
            </LockedSection>

          </div>
        </div>

        {/* POST TYPES SECTION – SORTABLE */}
        <div className="section-wrapper" ref={(el) => (sectionsRef.current['postTypes'] = el)}>
          <div className="settings-tab-containers">
            <h3 className='llms-heading'>Post Types</h3>

            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
              <SortableContext items={data.postTypes.map((pt) => pt.name)} strategy={verticalListSortingStrategy}>
                <ul className="sortable-list">
                  {data.postTypes.map((pt) => (
                    <React.Fragment key={pt.name}>
                      <SortablePostTypeItem
                        pt={pt}
                        data={data}
                        togglePostType={togglePostType}
                        onChange={onChange}
                        sublistOpen={sublistOpen}
                        onToggleSublist={onToggleSublist}
                      />

                      {/* PRODUCT SUBLIST */}
                      {pt.name === 'product' && sublistOpen.product && (
                        <LockedSection>
                          <div className="product-sublist">
                            <div className='product-sublist-container'>
                              {productToggleItems.map(({ id, label }) => (
                                <div className='product-sublist-input' key={id}>
                                  <input
                                    type="checkbox"
                                    id={id}
                                    checked={data.product?.[id] ?? false}
                                    onChange={(e) => toggleProduct(id, e.target.checked)}
                                  />
                                  <label htmlFor={id}>{label}</label>
                                </div>
                              ))}
                            </div>

                            {/* Exclude Categories */}
                            <div className="filter-section">
                              <label className="filter-label">Exclude Products By Categories</label>
                              <div className="tag-input-container" onClick={() => catInputRef.current?.focus()}>
                                <div className="existing-tags-categories">
                                  {(data.product?.excludeCategories || []).map((cat) => (
                                    <div className="tag" key={cat}>
                                      <span>{cat}</span>
                                      <button type="button" className="tag-remove" onClick={(e) => { e.stopPropagation(); removeExcludeCategory(cat); }}>×</button>
                                    </div>
                                  ))}
                                </div>
                                <div className="tag-input-wrapper" style={{ position: 'relative', flex: 1 }}>
                                  <input
                                    ref={catInputRef}
                                    type="text"
                                    className="tag-input"
                                    value={excludeCatInput}
                                    onChange={(e) => setExcludeCatInput(e.target.value)}
                                    onKeyDown={handleCatKeyDown}
                                    onFocus={() => excludeCatInput && setShowCatDropdown(true)}

                                  />
                                  {showCatDropdown && (
                                    <ul ref={catDropdownRef} className="tag-dropdown">
                                      {filteredCats.map((cat, idx) => (
                                        <li
                                          key={cat.id}
                                          className={`tag-dropdown-item ${idx === activeIndex ? 'active' : ''}`}
                                          onMouseDown={(e) => e.preventDefault()}
                                          onClick={() => addExcludeCategory(cat.name)}
                                        >
                                          {cat.name}
                                        </li>
                                      ))}
                                    </ul>
                                  )}
                                </div>
                              </div>
                            </div>

                            {/* Exclude Tags */}
                            <div className="filter-section">
                              <label className="filter-label">Exclude Products By Tags</label>
                              <div className="tag-input-container" onClick={() => tagInputRef.current?.focus()}>
                                <div className="existing-tags-categories">
                                  {(data.product?.excludeTags || []).map((tag) => (
                                    <div className="tag" key={tag}>
                                      <span>{tag}</span>
                                      <button type="button" className="tag-remove" onClick={(e) => { e.stopPropagation(); removeExcludeTag(tag); }}>×</button>
                                    </div>
                                  ))}
                                </div>
                                <div className="tag-input-wrapper" style={{ position: 'relative', flex: 1 }}>
                                  <input
                                    ref={tagInputRef}
                                    type="text"
                                    className="tag-input"
                                    value={excludeTagInput}
                                    onChange={(e) => setExcludeTagInput(e.target.value)}
                                    onKeyDown={handleTagKeyDown}
                                    onFocus={() => excludeTagInput && setShowTagDropdown(true)}

                                  />
                                  {showTagDropdown && (
                                    <ul ref={tagDropdownRef} className="tag-dropdown">
                                      {filteredTags.map((tag, idx) => (
                                        <li
                                          key={tag.id}
                                          className={`tag-dropdown-item ${idx === activeIndex ? 'active' : ''}`}
                                          onMouseDown={(e) => e.preventDefault()}
                                          onClick={() => addExcludeTag(tag.name)}
                                        >
                                          {tag.name}
                                        </li>
                                      ))}
                                    </ul>
                                  )}
                                </div>
                              </div>
                            </div>
                          </div>
                        </LockedSection>
                      )}

                      {/* POST SUBLIST - FULLY WORKING */}
                      {pt.name === 'post' && sublistOpen.post && (
                        <LockedSection>
                          <div className="product-sublist">
                            <div className='product-sublist-container'>
                              <div className='product-sublist-input'>
                                <input
                                  type="checkbox"
                                  id="post-showCategories"
                                  checked={data.post?.showCategories ?? true}
                                  onChange={(e) => toggleNested('post', 'showCategories', e.target.checked)}
                                />
                                <label htmlFor="post-showCategories">Show Post Categories</label>
                              </div>
                              <div className='product-sublist-input'>
                                <input
                                  type="checkbox"
                                  id="post-showTags"
                                  checked={data.post?.showTags ?? true}
                                  onChange={(e) => toggleNested('post', 'showTags', e.target.checked)}
                                />
                                <label htmlFor="post-showTags">Show Post Tags</label>
                              </div>
                            </div>

                            {/* Exclude Posts by Categories */}
                            <div className="filter-section">
                              <label className="filter-label">Exclude Posts By Categories</label>
                              <div className="tag-input-container" onClick={() => postCatInputRef.current?.focus()}>
                                <div className="existing-tags-categories">
                                  {(data.post?.excludeCategories || []).map((cat) => (
                                    <div className="tag" key={cat}>
                                      <span>{cat}</span>
                                      <button type="button" className="tag-remove" onClick={(e) => { e.stopPropagation(); removeExcludePostCategory(cat); }}>×</button>
                                    </div>
                                  ))}
                                </div>
                                <div className="tag-input-wrapper" style={{ position: 'relative', flex: 1 }}>
                                  <input
                                    ref={postCatInputRef}
                                    type="text"
                                    className="tag-input"
                                    value={excludePostCatInput}
                                    onChange={(e) => setExcludePostCatInput(e.target.value)}
                                    onKeyDown={handlePostCatKeyDown}
                                    onFocus={() => excludePostCatInput && setShowPostCatDropdown(true)}

                                  />
                                  {showPostCatDropdown && (
                                    <ul ref={postCatDropdownRef} className="tag-dropdown">
                                      {filteredPostCats.map((cat, idx) => (
                                        <li
                                          key={cat.id}
                                          className={`tag-dropdown-item ${idx === activePostIndex ? 'active' : ''}`}
                                          onMouseDown={(e) => e.preventDefault()}
                                          onClick={() => addExcludePostCategory(cat.name)}
                                        >
                                          {cat.name}
                                        </li>
                                      ))}
                                    </ul>
                                  )}
                                </div>
                              </div>
                            </div>

                            {/* Exclude Posts by Tags */}
                            <div className="filter-section">
                              <label className="filter-label">Exclude Posts By Tags</label>
                              <div className="tag-input-container" onClick={() => postTagInputRef.current?.focus()}>
                                <div className="existing-tags-categories">
                                  {(data.post?.excludeTags || []).map((tag) => (
                                    <div className="tag" key={tag}>
                                      <span>{tag}</span>
                                      <button type="button" className="tag-remove" onClick={(e) => { e.stopPropagation(); removeExcludePostTag(tag); }}>×</button>
                                    </div>
                                  ))}
                                </div>
                                <div className="tag-input-wrapper" style={{ position: 'relative', flex: 1 }}>
                                  <input
                                    ref={postTagInputRef}
                                    type="text"
                                    className="tag-input"
                                    value={excludePostTagInput}
                                    onChange={(e) => setExcludePostTagInput(e.target.value)}
                                    onKeyDown={handlePostTagKeyDown}
                                    onFocus={() => excludePostTagInput && setShowPostTagDropdown(true)}

                                  />
                                  {showPostTagDropdown && (
                                    <ul ref={postTagDropdownRef} className="tag-dropdown">
                                      {filteredPostTags.map((tag, idx) => (
                                        <li
                                          key={tag.id}
                                          className={`tag-dropdown-item ${idx === activePostIndex ? 'active' : ''}`}
                                          onMouseDown={(e) => e.preventDefault()}
                                          onClick={() => addExcludePostTag(tag.name)}
                                        >
                                          {tag.name}
                                        </li>
                                      ))}
                                    </ul>
                                  )}
                                </div>
                              </div>
                            </div>
                          </div>
                        </LockedSection>
                      )}
                    </React.Fragment>
                  ))}
                </ul>
              </SortableContext>
            </DndContext>
          </div>
        </div>

        {/* INCLUDE URLS */}
        <div className="section-wrapper" ref={(el) => (sectionsRef.current['includeUrls'] = el)}>
          <div className="settings-tab-containers">
            <h3 className='llms-heading'>Include URLs</h3>
            <div className="include-urls-container">
              <div className="include-urls-input-container">
                <input
                  type="text"
                  className="include-urls-textarea"
                  value={includeUrlInput}
                  onChange={(e) => setIncludeUrlInput(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      addUrl();
                    }
                  }}
                  placeholder="https://example.com/landing"
                />
              </div>
              <span className="add-btn-wrapper">
                <button
                  onClick={addUrl}
                  className="include-urls-add-button"
                  type="button"
                  disabled={!includeUrlInput.trim() || urlList.length > 2}
                >
                  Add
                </button>

                {urlList.length > 2 && (
                  <span className="pro-tooltip">Upgrade to Pro</span>
                )}
              </span>
            </div>

            <div className="include-urls-added-container">
              <div className="include-url-label-container">
                <label className="include-urls-label">Added URLs:</label>
              </div>
              {urlList.length === 0 ? (
                <p className="description">No URLs added yet.</p>
              ) : (
                urlList.map((url, idx) => (
                  <div key={idx} className="include-urls-added-rows-container">
                    <div className="added-urls-list">{url}</div>
                    <div className="incldues-urls-added-rows">
                      <button type="button" className="url-remove-btn" onClick={() => removeUrl(idx)}>
                        <TrashIcon size={16} color="#000000ff" />
                      </button>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>

        {/* EXCLUDE URLS */}
        <div className="section-wrapper" ref={(el) => (sectionsRef.current['excludeUrls'] = el)}>
          <div className="settings-tab-containers">
            <div className='settings-tab-sub-containers'>
              <h3 className='llms-heading'>Exclude URLs</h3>
              <textarea
                className="exclude-urls-textarea"
                rows="5"
                value={data.excludeUrls || ''}
                onChange={(e) => setField('excludeUrls', e.target.value)}
                placeholder="/private/*\n*.tmp"
              />
            </div>
            <div className="exclude-urls-description">
              <p className="description">
                Examples:<br />
                • <code>/private/*</code> (exclude all pages under private)<br />
                • <code>/draft-*</code> (exclude URLs starting with draft-)<br />
                • <code>*.tmp</code> (exclude files ending with .tmp)
              </p>
            </div>
          </div>
        </div>

        {/* UPDATE FREQUENCY */}
        <div className="section-wrapper" ref={(el) => (sectionsRef.current['updateFrequency'] = el)}>
          <div className="settings-tab-containers">
            <h3 className='llms-heading'>Update Frequency</h3>
            <div>
              <select
                className="tab-setting-select"
                value={data.updateFrequency || 'manual'}
                onChange={(e) => setField('updateFrequency', e.target.value)}
              >
                <option value="manual">Manual</option>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
              </select>
              <p className="description">
                Choose auto-update frequency or manual for on-demand updates
              </p>
            </div>
          </div>
        </div>

        {/* SEO SETTINGS */}
        <div className="section-wrapper" ref={(el) => (sectionsRef.current['seo'] = el)}>
          <div className="settings-tab-containers">
            <h3 className='llms-heading'>Respect SEO Settings</h3>
            <div className="seo-container">
              <ToggleSwitch
                id="respectSeo"
                checked={data.respectSeo || false}
                onChange={(e) => setField('respectSeo', e.target.checked)}
              />
              <p className="description">
                <strong>Exclude pages blocked by robots.txt or marked as noindex</strong><br />
                Compatible with Yoast SEO, Rank Math, SEOPress and All in One SEO
              </p>
            </div>
          </div>
        </div>

        {/* MULTILINGUAL */}
        <div className="section-wrapper" ref={(el) => (sectionsRef.current['multilingual'] = el)}>

          <LockedSection>
            <div className="settings-tab-containers">
              <h3 className='llms-heading'>Multilingual Support</h3>
              <div className="seo-container">
                <ToggleSwitch
                  id="multilingual"
                  checked={data.multilingual || false}
                  onChange={(e) => setField('multilingual', e.target.checked)}
                />
                <p className="description">
                  <strong>Multilingual Support</strong><br />
                  When enabled, llms-full.txt will organize content by language (requires WPML or Polylang).
                </p>
              </div>
            </div>
          </LockedSection>

          <div className="bottom-spacer" />
        </div>
      </div>
    </form>
  );
};

export default SettingTab;