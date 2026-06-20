/**
 * anyapi-order.js
 *
 * Order Integration wizard: create, edit, toggle, delete integrations.
 */

document.addEventListener("DOMContentLoaded", function () {
  const cfg = window.anyapiOrder || {};

  // ===========================================================================
  // State
  // ===========================================================================

  const state = {
    currentStep: 1,
    integrationId: 0, // 0 = new, >0 = editing
    saving: false,
    deleting: false,

    // Step 1
    apiKeyId: "", // ak_xxx ID

    // Step 3
    filterMode: "basic",
    selectedFields: [],
    searchQuery: "",
  };

  // ===========================================================================
  // DOM refs
  // ===========================================================================

  const wizardEl = document.getElementById("integration-wizard");
  const wizardTitle = document.getElementById("wizard-title");
  const wizardCloseBtn = document.getElementById("wizard-close-btn");
  const newBtn = document.getElementById("new-integration-btn");
  const listWrap = document.getElementById("integration-list-wrap");
  const emptyState = document.getElementById("integration-empty");
  const tbody = document.getElementById("integration-tbody");
  const integrationId = document.getElementById("integration-id");

  const stepPanels = document.querySelectorAll(".step-panel");
  const stepItems = document.querySelectorAll(".step-item");
  const progressFill = document.getElementById("progress-fill");

  const searchInput = document.getElementById("field-search");
  const searchResults = document.getElementById("search-results");
  const selectedList = document.getElementById("selected-fields-list");
  const selectedCount = document.getElementById("selected-count");
  const jsonPreview = document.getElementById("json-preview-content");
  const expertTextarea = document.getElementById("expert-json-textarea");
  const clearAllBtn = document.querySelector(".button-clear-all");
  const filterModeBtns = document.querySelectorAll(".filter-mode-btn");
  const finishBtn = document.getElementById("finish-btn");

  // ===========================================================================
  // WooCommerce field definitions
  // ===========================================================================

  const WC_FIELDS = [
    // Order
    { path: "id", label: "id", group: "Order" },
    { path: "parent_id", label: "parent_id", group: "Order" },
    { path: "number", label: "number", group: "Order" },
    { path: "order_key", label: "order_key", group: "Order" },
    { path: "status", label: "status", group: "Order" },
    { path: "currency", label: "currency", group: "Order" },
    { path: "date_created", label: "date_created", group: "Order" },
    { path: "date_modified", label: "date_modified", group: "Order" },
    { path: "discount_total", label: "discount_total", group: "Order" },
    { path: "shipping_total", label: "shipping_total", group: "Order" },
    { path: "total", label: "total", group: "Order" },
    { path: "total_tax", label: "total_tax", group: "Order" },
    { path: "customer_id", label: "customer_id", group: "Order" },
    { path: "customer_note", label: "customer_note", group: "Order" },
    { path: "payment_method", label: "payment_method", group: "Order" },
    {
      path: "payment_method_title",
      label: "payment_method_title",
      group: "Order",
    },
    { path: "transaction_id", label: "transaction_id", group: "Order" },
    { path: "date_paid", label: "date_paid", group: "Order" },
    { path: "date_completed", label: "date_completed", group: "Order" },
    { path: "cart_hash", label: "cart_hash", group: "Order" },
    { path: "created_via", label: "created_via", group: "Order" },
    // Billing
    { path: "billing.first_name", label: "first_name", group: "Billing" },
    { path: "billing.last_name", label: "last_name", group: "Billing" },
    { path: "billing.company", label: "company", group: "Billing" },
    { path: "billing.address_1", label: "address_1", group: "Billing" },
    { path: "billing.address_2", label: "address_2", group: "Billing" },
    { path: "billing.city", label: "city", group: "Billing" },
    { path: "billing.state", label: "state", group: "Billing" },
    { path: "billing.postcode", label: "postcode", group: "Billing" },
    { path: "billing.country", label: "country", group: "Billing" },
    { path: "billing.email", label: "email", group: "Billing" },
    { path: "billing.phone", label: "phone", group: "Billing" },
    // Shipping
    { path: "shipping.first_name", label: "first_name", group: "Shipping" },
    { path: "shipping.last_name", label: "last_name", group: "Shipping" },
    { path: "shipping.company", label: "company", group: "Shipping" },
    { path: "shipping.address_1", label: "address_1", group: "Shipping" },
    { path: "shipping.address_2", label: "address_2", group: "Shipping" },
    { path: "shipping.city", label: "city", group: "Shipping" },
    { path: "shipping.state", label: "state", group: "Shipping" },
    { path: "shipping.postcode", label: "postcode", group: "Shipping" },
    { path: "shipping.country", label: "country", group: "Shipping" },
    // Line Items
    { path: "line_items.id", label: "id", group: "Line Items" },
    { path: "line_items.name", label: "name", group: "Line Items" },
    { path: "line_items.product_id", label: "product_id", group: "Line Items" },
    {
      path: "line_items.variation_id",
      label: "variation_id",
      group: "Line Items",
    },
    { path: "line_items.quantity", label: "quantity", group: "Line Items" },
    { path: "line_items.subtotal", label: "subtotal", group: "Line Items" },
    { path: "line_items.total", label: "total", group: "Line Items" },
    { path: "line_items.sku", label: "sku", group: "Line Items" },
    { path: "line_items.price", label: "price", group: "Line Items" },
    // Shipping Lines
    { path: "shipping_lines.id", label: "id", group: "Shipping Lines" },
    {
      path: "shipping_lines.method_title",
      label: "method_title",
      group: "Shipping Lines",
    },
    {
      path: "shipping_lines.method_id",
      label: "method_id",
      group: "Shipping Lines",
    },
    { path: "shipping_lines.total", label: "total", group: "Shipping Lines" },
    // Refunds
    { path: "refunds.id", label: "id", group: "Refunds" },
    { path: "refunds.reason", label: "reason", group: "Refunds" },
    { path: "refunds.total", label: "total", group: "Refunds" },
    // Meta
    { path: "meta_data", label: "meta_data (all)", group: "Meta Data" },
  ];

  const GROUP_COLORS = {
    Order: "#7c3aed",
    Billing: "#0ea5e9",
    Shipping: "#10b981",
    "Line Items": "#f59e0b",
    "Shipping Lines": "#06b6d4",
    "Tax Lines": "#ef4444",
    "Fee Lines": "#8b5cf6",
    "Coupon Lines": "#ec4899",
    Refunds: "#f97316",
    "Meta Data": "#64748b",
  };

  // ===========================================================================
  // Wizard open / close
  // ===========================================================================

  function openWizard(editId = 0) {
    resetWizard();
    state.integrationId = editId;

    if (integrationId) integrationId.value = editId;

    if (editId > 0) {
      wizardTitle &&
        (wizardTitle.textContent = cfg.i18n?.edit_title || "Edit Integration");
      loadIntegration(editId);
    } else {
      wizardTitle &&
        (wizardTitle.textContent = cfg.i18n?.new_title || "New Integration");
    }

    wizardEl?.classList.remove("is-hidden");
    wizardEl?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function closeWizard() {
    wizardEl?.classList.add("is-hidden");
    resetWizard();
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function resetWizard() {
    state.currentStep = 1;
    state.integrationId = 0;
    state.apiKeyId = "";
    state.filterMode = "basic";
    state.selectedFields = [];
    state.searchQuery = "";

    // Clear inputs
    ["integration-name", "api-url", "api-payload"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = "";
    });

    const keySelect = document.getElementById("api-key-select");
    if (keySelect) keySelect.value = "";

    if (expertTextarea) expertTextarea.value = "";

    // Reset filter mode buttons
    filterModeBtns.forEach((b) => {
      b.classList.toggle("active", b.dataset.mode === "basic");
    });

    // Reset trigger tiles
    document
      .querySelectorAll(".action-tile")
      .forEach((t) => t.classList.remove("active"));

    // Reset hidden id
    if (integrationId) integrationId.value = "0";

    renderSelectedFields();
    updateJsonPreview();
    updateFilterModeUI();
    switchStep(1);
  }

  // Open wizard buttons
  newBtn?.addEventListener("click", () => openWizard(0));
  wizardCloseBtn?.addEventListener("click", closeWizard);
  document
    .querySelectorAll(".js-open-wizard")
    .forEach((b) => b.addEventListener("click", () => openWizard(0)));

  // ===========================================================================
  // Load integration for editing (AJAX)
  // ===========================================================================

  async function loadIntegration(id) {
    const fd = new FormData();
    fd.append("action", "anyapi_load_integration");
    fd.append("nonce", cfg.nonce);
    fd.append("integration_id", id);

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();
      if (data.success) {
        fillWizard(data.data.record);
      }
    } catch {
      // silently fail — wizard stays blank for re-entry
    }
  }

  /**
   * Fill wizard form from a full record (from ajaxLoad response).
   * record has: id, name, api_url, api_key_id, payload,
   *             trigger, filter_mode, selected_fields, field_order,
   *             raw_json_override
   */
  function fillWizard(record) {
    // Step 1
    const nameEl = document.getElementById("integration-name");
    const urlEl = document.getElementById("api-url");
    const payloadEl = document.getElementById("api-payload");
    const keySelect = document.getElementById("api-key-select");

    if (nameEl) nameEl.value = record.name || "";
    if (urlEl) urlEl.value = record.api_url || "";
    if (payloadEl) payloadEl.value = record.payload || "";

    if (keySelect && record.api_key_id) {
      keySelect.value = record.api_key_id;
      state.apiKeyId = record.api_key_id;
    }

    // Step 2 — select the trigger tile
    const triggerSlug = record.trigger || "";
    document.querySelectorAll(".action-tile").forEach((t) => {
      t.classList.toggle("active", t.dataset.action === triggerSlug);
    });

    // Step 3 — filter mode
    const mode = record.filter_mode || "basic";
    state.filterMode = mode;
    state.selectedFields = record.selected_fields || record.field_order || [];

    filterModeBtns.forEach((b) =>
      b.classList.toggle("active", b.dataset.mode === mode),
    );

    if (expertTextarea) {
      expertTextarea.value = record.raw_json_override || "";
    }

    updateFilterModeUI();
    renderSelectedFields();
    updateJsonPreview();
  }

  // ===========================================================================
  // Step navigation
  // ===========================================================================

  function switchStep(step) {
    stepPanels.forEach((p) => p.classList.remove("active"));
    stepItems.forEach((i) => i.classList.remove("active"));

    document
      .querySelector(`.step-panel[data-step="${step}"]`)
      ?.classList.add("active");
    document
      .querySelector(`.step-item[data-step="${step}"]`)
      ?.classList.add("active");
    state.currentStep = step;

    if (progressFill) {
      progressFill.style.width = `${((step - 1) / 3) * 100}%`;
    }

    stepItems.forEach((item) => {
      item.classList.toggle("complete", parseInt(item.dataset.step) < step);
    });

    if (step === 4) renderSummary();
    wizardEl?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  document.querySelectorAll(".next-step").forEach((btn) => {
    btn.addEventListener("click", function () {
      if (validateStep(state.currentStep))
        switchStep(parseInt(this.dataset.next));
    });
  });

  document.querySelectorAll(".prev-step").forEach((btn) => {
    btn.addEventListener("click", function () {
      switchStep(parseInt(this.dataset.prev));
    });
  });

  // ===========================================================================
  // Validation
  // ===========================================================================

  function clearErrors() {
    document
      .querySelectorAll(".error-message")
      .forEach((e) => (e.textContent = ""));
  }

  function showError(id, msg) {
    const el = document.getElementById(id);
    if (el) el.textContent = msg;
  }

  function validateStep(step) {
    clearErrors();

    if (step === 1) {
      const url = (document.getElementById("api-url")?.value || "").trim();
      const keyId = (
        document.getElementById("api-key-select")?.value || ""
      ).trim();
      const payload = (
        document.getElementById("api-payload")?.value || ""
      ).trim();

      if (!url) {
        showError(
          "url-error",
          cfg.i18n?.url_required || "API URL is required.",
        );
        return false;
      }
      try {
        const u = new URL(url);
        if (u.protocol !== "https:") throw new Error();
      } catch {
        showError(
          "url-error",
          cfg.i18n?.url_invalid || "URL must be a valid https:// address.",
        );
        return false;
      }
      if (!keyId) {
        showError(
          "key-error",
          cfg.i18n?.key_required || "Please select an API Key.",
        );
        return false;
      }
      // Payload is optional. Empty = full order data (Basic) or
      // controlled by the Step 3 filter (advanced/expert). Validate JSON
      // only when a value is present; no {{variable}} substitution in Basic.
      if (payload) {
        try {
          JSON.parse(payload);
        } catch {
          showError(
            "payload-error",
            cfg.i18n?.payload_invalid || "Invalid JSON payload format.",
          );
          return false;
        }
      }
    }

    if (step === 2) {
      if (!document.querySelector(".action-tile.active")) {
        showError(
          "action-error",
          cfg.i18n?.trigger_required || "Please select a trigger action.",
        );
        return false;
      }
    }

    if (step === 3) {
      if (state.filterMode === "expert" && expertTextarea?.value.trim()) {
        try {
          JSON.parse(expertTextarea.value.trim());
        } catch {
          showError(
            "expert-error",
            "Raw JSON is not valid. Please fix before continuing.",
          );
          return false;
        }
      }
    }

    return true;
  }

  // ===========================================================================
  // Action Tiles (Step 2) — plan gate
  // ===========================================================================

  document.querySelectorAll(".action-tile").forEach((tile) => {
    tile.addEventListener("click", function () {
      if (this.dataset.locked === "1") {
        openUpgradeModal(cfg.i18n?.trigger_locked);
        return;
      }
      document
        .querySelectorAll(".action-tile")
        .forEach((t) => t.classList.remove("active"));
      this.classList.add("active");
    });
    // Keyboard support
    tile.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        tile.click();
      }
    });
  });

  // ===========================================================================
  // Filter Mode (Step 3) — plan gate
  // ===========================================================================

  filterModeBtns?.forEach((btn) => {
    btn.addEventListener("click", function () {
      if (this.dataset.locked === "1") {
        openUpgradeModal(cfg.i18n?.filter_locked);
        return;
      }
      filterModeBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      state.filterMode = this.dataset.mode;
      updateFilterModeUI();
      updateJsonPreview();
    });
  });

  // Live-update the payload hint as the user types.
  document
    .getElementById("api-payload")
    ?.addEventListener("input", updatePayloadHint);

  // Update the Step 1 payload hint based on filter mode + content.
  function updatePayloadHint() {
    const hintEl = document.getElementById("payload-hint");
    if (!hintEl) return;
    const payload = (
      document.getElementById("api-payload")?.value || ""
    ).trim();
    let msg;
    if (state.filterMode === "advanced" || state.filterMode === "expert") {
      msg =
        cfg.i18n?.payload_hint_filter ||
        "Payload is controlled by your Step 3 filter settings.";
    } else if (payload === "") {
      msg =
        cfg.i18n?.payload_hint_empty ||
        "Leave empty to send the full WooCommerce order data.";
    } else {
      msg =
        cfg.i18n?.payload_hint_static ||
        "This static JSON will be sent exactly as written. {{variables}} are not supported in Basic mode.";
    }
    hintEl.textContent = msg;
  }

  function updateFilterModeUI() {
    const basic = document.getElementById("mode-basic");
    const advanced = document.getElementById("mode-advanced");
    const expert = document.getElementById("mode-expert");
    if (basic)
      basic.style.display = state.filterMode === "basic" ? "block" : "none";
    if (advanced)
      advanced.style.display =
        state.filterMode === "advanced" || state.filterMode === "expert"
          ? "block"
          : "none";
    if (expert)
      expert.style.display = state.filterMode === "expert" ? "block" : "none";
    updatePayloadHint();
  }

  // ===========================================================================
  // api-key-select change → update state.apiKeyId
  // ===========================================================================

  document
    .getElementById("api-key-select")
    ?.addEventListener("change", function () {
      state.apiKeyId = this.value; // ak_xxx or ""
    });

  // ===========================================================================
  // Step 3: Field search & selection
  // ===========================================================================

  function getFilteredFields(query) {
    if (!query) return [];
    const q = query.toLowerCase();
    return WC_FIELDS.filter(
      (f) =>
        f.path.toLowerCase().includes(q) ||
        f.label.toLowerCase().includes(q) ||
        f.group.toLowerCase().includes(q),
    ).slice(0, 12);
  }

  function renderSearchResults(query) {
    if (!searchResults) return;
    if (!query) {
      searchResults.innerHTML = "";
      searchResults.style.display = "none";
      return;
    }

    const results = getFilteredFields(query);
    if (results.length === 0) {
      searchResults.innerHTML =
        '<div class="search-no-results">No fields found</div>';
      searchResults.style.display = "block";
      return;
    }

    searchResults.innerHTML = results
      .map((f) => {
        const isSelected = state.selectedFields.includes(f.path);
        const color = GROUP_COLORS[f.group] || "#64748b";
        return `<button class="search-result-item ${isSelected ? "is-selected" : ""}"
                data-path="${f.path}" type="button">
        <span class="result-path">${highlightMatch(f.path, query)}</span>
        <span class="result-group" style="background:${color}20;color:${color}">${f.group}</span>
        ${isSelected ? '<span class="result-check">✓</span>' : '<span class="result-add">+</span>'}
      </button>`;
      })
      .join("");

    searchResults.style.display = "block";
    searchResults
      .querySelectorAll(".search-result-item:not(.is-selected)")
      .forEach((item) => {
        item.addEventListener("click", () => {
          addField(item.dataset.path);
          renderSearchResults(state.searchQuery);
        });
      });
  }

  function highlightMatch(text, query) {
    const idx = text.toLowerCase().indexOf(query.toLowerCase());
    if (idx === -1) return text;
    return (
      text.slice(0, idx) +
      `<mark>${text.slice(idx, idx + query.length)}</mark>` +
      text.slice(idx + query.length)
    );
  }

  function addField(path) {
    if (state.selectedFields.includes(path)) return;
    state.selectedFields.push(path);
    renderSelectedFields();
    updateJsonPreview();
  }

  function removeField(path) {
    state.selectedFields = state.selectedFields.filter((f) => f !== path);
    renderSelectedFields();
    updateJsonPreview();
    if (state.searchQuery) renderSearchResults(state.searchQuery);
  }

  function renderSelectedFields() {
    if (!selectedList) return;
    if (selectedCount) selectedCount.textContent = state.selectedFields.length;

    if (state.selectedFields.length === 0) {
      selectedList.innerHTML =
        '<p class="no-fields-hint">Search for fields above and click to add them here.</p>';
      return;
    }

    const ARRAY_GROUPS = [
      "line_items",
      "tax_lines",
      "shipping_lines",
      "fee_lines",
      "coupon_lines",
      "refunds",
    ];

    selectedList.innerHTML = state.selectedFields
      .map((path, index) => {
        const fieldDef = WC_FIELDS.find((f) => f.path === path);
        const group = fieldDef?.group || "";
        const color = GROUP_COLORS[group] || "#64748b";
        const isArray = ARRAY_GROUPS.some((g) => path.startsWith(g + "."));
        return `<div class="selected-field${isArray ? " is-array" : ""}" draggable="true"
                   data-field="${path}" data-index="${index}">
        <span class="drag-handle">⠿</span>
        <span class="field-path">${path}</span>
        ${group ? `<span class="field-group-badge" style="background:${color}20;color:${color}">${group}</span>` : ""}
        <button class="remove-field" data-path="${path}" title="Remove" type="button">×</button>
      </div>`;
      })
      .join("");

    selectedList.querySelectorAll(".remove-field").forEach((btn) => {
      btn.addEventListener("click", () => removeField(btn.dataset.path));
    });
    initDragAndDrop();
  }

  function initDragAndDrop() {
    if (!selectedList) return;
    let dragSrc = null;
    selectedList.querySelectorAll(".selected-field").forEach((item) => {
      item.addEventListener("dragstart", (e) => {
        dragSrc = item;
        item.classList.add("dragging");
        e.dataTransfer.effectAllowed = "move";
      });
      item.addEventListener("dragend", () => {
        item.classList.remove("dragging");
        selectedList
          .querySelectorAll(".selected-field")
          .forEach((i) => i.classList.remove("drag-over"));
        state.selectedFields = Array.from(
          selectedList.querySelectorAll(".selected-field"),
        ).map((el) => el.dataset.field);
        updateJsonPreview();
      });
      item.addEventListener("dragover", (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = "move";
        selectedList
          .querySelectorAll(".selected-field")
          .forEach((i) => i.classList.remove("drag-over"));
        if (item !== dragSrc) item.classList.add("drag-over");
      });
      item.addEventListener("drop", (e) => {
        e.preventDefault();
        if (dragSrc && dragSrc !== item) {
          const all = Array.from(
            selectedList.querySelectorAll(".selected-field"),
          );
          all.indexOf(dragSrc) < all.indexOf(item)
            ? item.after(dragSrc)
            : item.before(dragSrc);
        }
      });
    });
  }

  // JSON preview
  function buildJsonStructure(fields) {
    const ARRAY_GROUPS = [
      "line_items",
      "tax_lines",
      "shipping_lines",
      "fee_lines",
      "coupon_lines",
      "refunds",
    ];
    const result = {};
    fields.forEach((path) => {
      const parts = path.split(".");
      let cur = result;
      parts.forEach((part, idx) => {
        const isLast = idx === parts.length - 1;
        const isArray = ARRAY_GROUPS.includes(part) && !isLast;
        if (isArray) {
          if (!cur[part]) cur[part] = [{}];
          cur = cur[part][0];
        } else if (isLast) {
          cur[part] = "";
        } else {
          if (!cur[part]) cur[part] = {};
          cur = cur[part];
        }
      });
    });
    return result;
  }

  function updateJsonPreview() {
    if (!jsonPreview) return;
    if (state.filterMode === "expert" && expertTextarea?.value.trim()) {
      try {
        jsonPreview.textContent = JSON.stringify(
          JSON.parse(expertTextarea.value.trim()),
          null,
          2,
        );
      } catch {
        jsonPreview.textContent = expertTextarea.value;
      }
      return;
    }
    if (state.selectedFields.length === 0) {
      jsonPreview.textContent = "// No fields selected yet";
      return;
    }
    jsonPreview.textContent = JSON.stringify(
      buildJsonStructure(state.selectedFields),
      null,
      2,
    );
  }

  expertTextarea?.addEventListener("input", updateJsonPreview);
  clearAllBtn?.addEventListener("click", () => {
    state.selectedFields = [];
    renderSelectedFields();
    updateJsonPreview();
    if (state.searchQuery) renderSearchResults(state.searchQuery);
  });

  if (searchInput) {
    searchInput.addEventListener("input", function () {
      state.searchQuery = this.value.trim();
      renderSearchResults(state.searchQuery);
    });
    searchInput.addEventListener("focus", () => {
      if (state.searchQuery) renderSearchResults(state.searchQuery);
    });
    document.addEventListener("click", (e) => {
      if (
        !searchInput.contains(e.target) &&
        !searchResults?.contains(e.target)
      ) {
        if (searchResults) searchResults.style.display = "none";
      }
    });
  }

  // ===========================================================================
  // Step 4: Summary
  // ===========================================================================

  function renderSummary() {
    const keyId = document.getElementById("api-key-select")?.value || "";
    const keyName = getKeyName(keyId);
    const trigger =
      document.querySelector(".action-tile.active h3")?.textContent || "—";
    const mode =
      state.filterMode.charAt(0).toUpperCase() + state.filterMode.slice(1);
    const fields =
      state.filterMode === "basic"
        ? "All Fields"
        : state.selectedFields.length + " fields selected";

    const set = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val;
    };
    set(
      "summary-name",
      document.getElementById("integration-name")?.value || "—",
    );
    set("summary-url", document.getElementById("api-url")?.value || "—");
    set("summary-key", keyName || keyId || "—");
    set("summary-trigger", trigger);
    set("summary-mode", mode);
    set("summary-fields", fields);
  }

  function getKeyName(akId) {
    if (!akId || !cfg.api_keys) return "";
    const k = cfg.api_keys.find((k) => k.id === akId);
    return k ? k.name : akId;
  }

  // ===========================================================================
  // Save (Finish button)
  // ===========================================================================

  finishBtn?.addEventListener("click", async function () {
    if (state.saving) return;
    state.saving = true;
    this.disabled = true;
    this.textContent = cfg.i18n?.saving || "Saving…";

    const keyId = document.getElementById("api-key-select")?.value || "";

    const fd = new FormData();
    fd.append("action", "anyapi_save_integration");
    fd.append("nonce", cfg.nonce);
    fd.append(
      "integration_id",
      document.getElementById("integration-id")?.value || "0",
    );
    fd.append("name", document.getElementById("integration-name")?.value || "");
    fd.append("api_url", document.getElementById("api-url")?.value || "");
    fd.append("api_key_id", keyId);
    fd.append("payload", document.getElementById("api-payload")?.value || "");
    fd.append(
      "trigger",
      document.querySelector(".action-tile.active")?.dataset.action || "",
    );
    fd.append("http_method", "POST");
    fd.append("headers", "[]");
    fd.append("filter_mode", state.filterMode);
    fd.append("selected_fields", JSON.stringify(state.selectedFields));
    fd.append("field_order", JSON.stringify(state.selectedFields));
    fd.append("raw_json_override", expertTextarea?.value || "");

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();

      if (data.success) {
        const record = data.data.record;
        state.integrationId = record.id;
        upsertRow(record);
        showSaveStatus(
          "success",
          cfg.i18n?.save_success || "Integration saved!",
        );
        setTimeout(() => closeWizard(), 1800);
      } else {
        if (data.data?.gate) {
          openUpgradeModal(data.data.message);
        } else {
          showSaveStatus("error", data.data?.message || cfg.i18n?.save_error);
        }
      }
    } catch {
      showSaveStatus("error", cfg.i18n?.save_error || "Save failed.");
    } finally {
      state.saving = false;
      this.disabled = false;
      this.textContent = "💾 " + (cfg.i18n?.save_btn || "Save & Finish");
    }
  });

  function showSaveStatus(type, msg) {
    const el = document.getElementById("save-status");
    if (!el) return;
    el.textContent = msg;
    el.className = `save-status save-status--${type}`;
    el.style.display = "block";
    if (type === "success") setTimeout(() => (el.style.display = "none"), 3000);
  }

  // ===========================================================================
  // Integration list: toggle / edit / delete
  // ===========================================================================

  // Toggle active/inactive
  tbody?.addEventListener("change", async function (e) {
    const input = e.target.closest(".js-toggle-integration");
    if (!input) return;

    const id = input.dataset.id;
    const active = input.checked;
    const fd = new FormData();
    fd.append("action", "anyapi_toggle_integration");
    fd.append("nonce", cfg.nonce);
    fd.append("integration_id", id);
    fd.append("active", active ? "1" : "0");

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();
      if (data.success) {
        const row = tbody.querySelector(`tr[data-id="${id}"]`);
        row?.classList.toggle("is-inactive", !active);
      } else {
        input.checked = !active; // revert
      }
    } catch {
      input.checked = !active;
    }
  });

  // Edit button
  tbody?.addEventListener("click", function (e) {
    const editBtn = e.target.closest(".js-edit-integration");
    if (editBtn) {
      openWizard(parseInt(editBtn.dataset.id));
    }
  });

  // Delete button
  tbody?.addEventListener("click", async function (e) {
    const delBtn = e.target.closest(".js-delete-integration");
    if (!delBtn) return;

    if (!confirm(cfg.i18n?.delete_confirm || "Delete this integration?"))
      return;

    const id = delBtn.dataset.id;
    const fd = new FormData();
    fd.append("action", "anyapi_delete_integration");
    fd.append("nonce", cfg.nonce);
    fd.append("integration_id", id);

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();
      if (data.success) {
        removeRow(id);
      }
    } catch {
      /* silent */
    }
  });

  // ===========================================================================
  // List DOM helpers
  // ===========================================================================

  /**
   * Insert or update a row in the integration table.
   * record = safeRecord from PHP: { id, name, api_url, api_key_id, trigger, filter_mode, status }
   */
  function upsertRow(record) {
    const existing = tbody?.querySelector(`tr[data-id="${record.id}"]`);
    const keyName = getKeyName(record.api_key_id);
    const isActive = record.status === "active";

    const tr = document.createElement("tr");
    tr.dataset.id = record.id;
    tr.className = `oi-row ${isActive ? "" : "is-inactive"}`;
    tr.innerHTML = `
      <td class="oi-col-name">
        <strong>${escHtml(record.name || "Integration #" + record.id)}</strong>
        ${keyName ? `<span class="oi-key-badge">🔑 ${escHtml(keyName)}</span>` : ""}
      </td>
      <td>${escHtml(record.trigger)}</td>
      <td class="oi-col-url"><code title="${escHtml(record.api_url)}">${escHtml(record.api_url.length > 40 ? record.api_url.slice(0, 40) + "…" : record.api_url)}</code></td>
      <td>${escHtml(record.filter_mode.charAt(0).toUpperCase() + record.filter_mode.slice(1))}</td>
      <td>
        <label class="oi-toggle">
          <input type="checkbox" class="oi-toggle__input js-toggle-integration" data-id="${record.id}" ${isActive ? "checked" : ""}>
          <span class="oi-toggle__track"></span>
        </label>
      </td>
      <td class="oi-col-actions">
        <button type="button" class="oi-btn oi-btn--sm oi-btn--ghost js-edit-integration" data-id="${record.id}">✏️ Edit</button>
        <button type="button" class="oi-btn oi-btn--sm oi-btn--danger-ghost js-delete-integration" data-id="${record.id}">🗑</button>
      </td>`;

    if (existing) {
      existing.replaceWith(tr);
    } else {
      tbody?.appendChild(tr);
    }

    // Show table, hide empty state
    listWrap?.classList.remove("is-hidden");
    emptyState?.classList.add("is-hidden");
  }

  function removeRow(id) {
    tbody?.querySelector(`tr[data-id="${id}"]`)?.remove();
    if (tbody && tbody.rows.length === 0) {
      listWrap?.classList.add("is-hidden");
      emptyState?.classList.remove("is-hidden");
    }
  }

  function escHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  // ===========================================================================
  // Upgrade Modal
  // ===========================================================================

  const upgradeModal = document.getElementById("anyapi-upgrade-modal");
  const modalBody = document.getElementById("modal-body");
  const modalCloseBtn = upgradeModal?.querySelector(".upgrade-modal__close");
  const modalBackdrop = upgradeModal?.querySelector(".upgrade-modal__backdrop");

  function openUpgradeModal(reason) {
    if (!upgradeModal) return;
    if (modalBody) modalBody.textContent = reason || "";
    upgradeModal.style.display = "flex";
    document.body.style.overflow = "hidden";
    setTimeout(() => modalCloseBtn?.focus(), 50);
  }

  function closeUpgradeModal() {
    if (!upgradeModal) return;
    upgradeModal.style.display = "none";
    document.body.style.overflow = "";
  }

  modalCloseBtn?.addEventListener("click", closeUpgradeModal);
  modalBackdrop?.addEventListener("click", closeUpgradeModal);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && upgradeModal?.style.display === "flex")
      closeUpgradeModal();
  });

  // ===========================================================================
  // Init
  // ===========================================================================

  updateFilterModeUI();
  renderSelectedFields();
  updateJsonPreview();
  switchStep(1);
});
