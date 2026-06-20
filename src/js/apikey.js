/**
 * settings-apikey.js
 *
 * API Key CRUD: add/edit form, auth type switcher, AJAX save/delete/toggle, plan limit gate.
 */

document.addEventListener("DOMContentLoaded", function () {
  // =========================================================================
  // Config from wp_localize_script
  // =========================================================================
  const cfg = window.anyapiApiKey || {};

  // =========================================================================
  // DOM refs
  // =========================================================================
  const wrap = document.getElementById("anyapi-apikey");
  if (!wrap) return;

  const formWrap = document.getElementById("ak-form-wrap");
  const formTitle = document.getElementById("ak-form-title");
  const addBtn = document.getElementById("ak-add-btn");
  const emptyAddBtn = document.getElementById("ak-empty-add-btn");
  const cancelBtn = document.getElementById("ak-cancel-btn");
  const closeBtn = document.getElementById("ak-form-close");
  const saveBtn = document.getElementById("ak-save-btn");
  const saveLabel = document.getElementById("ak-save-label");
  const grid = document.getElementById("ak-grid");
  const formStatus = document.getElementById("ak-form-status");

  // Form fields
  const editIdInput = document.getElementById("ak-edit-id");
  const nameInput = document.getElementById("ak-name");
  const typeInput = document.getElementById("ak-type");
  const typeBtns = document.querySelectorAll(".ak-type-btn");
  const tokenInput = document.getElementById("ak-token");
  const userInput = document.getElementById("ak-username");
  const passInput = document.getElementById("ak-password");
  const statusCheck = document.getElementById("ak-status");

  const fieldsBearer = document.getElementById("ak-fields-bearer");
  const fieldsBasic = document.getElementById("ak-fields-basic");

  // =========================================================================
  // State
  // =========================================================================
  let saving = false;
  let deleting = false;

  // =========================================================================
  // Helpers
  // =========================================================================

  function clearErrors() {
    document
      .querySelectorAll(".ak-error")
      .forEach((el) => (el.textContent = ""));
  }

  function showFieldError(id, msg) {
    const el = document.getElementById(id);
    if (el) el.textContent = msg;
  }

  function setFormStatus(msg, type = "success") {
    if (!formStatus) return;
    formStatus.textContent = msg;
    formStatus.className = `ak-form-status ak-form-status--${type}`;
    formStatus.style.display = msg ? "block" : "none";
  }

  function setCardStatus(card, status) {
    card.classList.toggle("is-active", status === "active");
    card.classList.toggle("is-inactive", status !== "active");
    card.dataset.status = status;

    const badge = card.querySelector(".ak-badge--status");
    if (badge) {
      badge.textContent =
        status === "active"
          ? cfg.i18n?.active || "Active"
          : cfg.i18n?.inactive || "Inactive";
      badge.classList.toggle("is-active", status === "active");
      badge.classList.toggle("is-inactive", status !== "active");
    }
  }

  function refreshUsageBar(count) {
    const limit = cfg.key_limit;
    if (limit < 0) return; // unlimited plan

    const bar = document.querySelector(".ak-quota__fill");
    const label = document.querySelector(".ak-quota__label");
    const pct = Math.min(100, Math.round((count / limit) * 100));

    if (bar) {
      bar.style.width = pct + "%";
      bar.classList.toggle("is-full", pct >= 100);
      bar.classList.toggle("is-warn", pct >= 80 && pct < 100);
    }
    if (label) {
      label.textContent = `${count} / ${limit} keys used`;
    }

    // Gate the Add button
    if (addBtn) {
      const hit = pct >= 100;
      addBtn.disabled = hit;
      addBtn.dataset.limitHit = hit ? "1" : "0";
      addBtn.classList.toggle("is-disabled", hit);
    }
  }

  // =========================================================================
  // Form open / close
  // =========================================================================

  function openForm(mode = "new", cardData = null) {
    clearErrors();
    setFormStatus("");

    if (mode === "new") {
      formTitle.textContent = cfg.i18n?.new_key_title || "New API Key";
      editIdInput.value = "";
      nameInput.value = "";
      tokenInput.value = "";
      userInput.value = "";
      passInput.value = "";
      statusCheck.checked = true;
      setAuthType("bearer");
    } else if (cardData) {
      formTitle.textContent = cfg.i18n?.edit_key_title || "Edit API Key";
      editIdInput.value = cardData.id;
      nameInput.value = cardData.name;
      statusCheck.checked = cardData.status === "active";
      setAuthType(cardData.type || "bearer");
      // Credentials are not pre-filled for security; show placeholder hint
      tokenInput.value = "";
      userInput.value = "";
      passInput.value = "";
      tokenInput.placeholder =
        cardData.type === "bearer"
          ? "(leave blank to keep existing token)"
          : "";
      passInput.placeholder =
        cardData.type === "basic"
          ? "(leave blank to keep existing password)"
          : "";
    }

    formWrap.style.display = "block";
    formWrap.removeAttribute("aria-hidden");
    formWrap.scrollIntoView({ behavior: "smooth", block: "start" });
    nameInput.focus();
  }

  function closeForm() {
    formWrap.style.display = "block"; // keep in DOM during animation
    formWrap.classList.add("is-closing");
    setTimeout(() => {
      formWrap.style.display = "none";
      formWrap.setAttribute("aria-hidden", "true");
      formWrap.classList.remove("is-closing");
      clearErrors();
    }, 200);
  }

  // =========================================================================
  // Auth type switcher
  // =========================================================================

  function setAuthType(type) {
    typeInput.value = type;

    typeBtns.forEach((btn) => {
      const active = btn.dataset.type === type;
      btn.classList.toggle("active", active);
      btn.setAttribute("aria-checked", active ? "true" : "false");
    });

    if (type === "bearer") {
      fieldsBearer.style.display = "block";
      fieldsBasic.style.display = "none";
    } else {
      fieldsBearer.style.display = "none";
      fieldsBasic.style.display = "block";
    }
  }

  typeBtns.forEach((btn) => {
    btn.addEventListener("click", () => setAuthType(btn.dataset.type));
  });

  // =========================================================================
  // Eye (password reveal) buttons inside form
  // =========================================================================

  document.querySelectorAll(".ak-eye-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetId = btn.dataset.target;
      const input = document.getElementById(targetId);
      if (!input) return;
      const shown = input.type === "text";
      input.type = shown ? "password" : "text";
      btn.querySelector(".ak-eye-icon").textContent = shown ? "👁" : "🙈";
    });
  });

  // =========================================================================
  // Add button
  // =========================================================================

  function handleAddClick() {
    if (addBtn?.dataset.limitHit === "1") {
      // Soft-block: show inline message or redirect
      const url = cfg.upgrade_url;
      if (url) window.open(url, "_blank", "noopener");
      return;
    }
    openForm("new");
  }

  addBtn?.addEventListener("click", handleAddClick);
  emptyAddBtn?.addEventListener("click", handleAddClick);

  cancelBtn?.addEventListener("click", closeForm);
  closeBtn?.addEventListener("click", closeForm);

  // =========================================================================
  // Save via AJAX
  // =========================================================================

  saveBtn?.addEventListener("click", async function () {
    if (saving) return;

    clearErrors();
    setFormStatus("");

    const type = typeInput.value;
    const editId = editIdInput.value;

    // ── Client-side validation ─────────────────────────────────────────────
    let valid = true;

    if (!nameInput.value.trim()) {
      showFieldError("ak-name-error", "Key name is required.");
      valid = false;
    }
    if (type === "bearer" && !editId && !tokenInput.value.trim()) {
      showFieldError("ak-token-error", "Bearer token is required.");
      valid = false;
    }
    if (type === "basic" && !editId) {
      if (!userInput.value.trim()) {
        showFieldError("ak-username-error", "Username is required.");
        valid = false;
      }
      if (!passInput.value.trim()) {
        showFieldError("ak-password-error", "Password is required.");
        valid = false;
      }
    }
    if (!valid) return;

    // ── Submit ─────────────────────────────────────────────────────────────
    saving = true;
    saveBtn.disabled = true;
    saveLabel.textContent = cfg.i18n?.saving || "Saving…";

    const fd = new FormData();
    fd.append("action", "anyapi_save_apikey");
    fd.append("nonce", cfg.nonce);
    fd.append("id", editId);
    fd.append("name", nameInput.value.trim());
    fd.append("type", type);
    fd.append("key", tokenInput.value.trim());
    fd.append("username", userInput.value.trim());
    fd.append("password", passInput.value.trim());
    fd.append("status", statusCheck.checked ? "active" : "inactive");

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();

      if (data.success) {
        setFormStatus(cfg.i18n?.save_success || "Saved!", "success");
        refreshUsageBar(data.data.count);

        if (editId) {
          // Update existing card in DOM
          updateCard(editId, {
            name: nameInput.value.trim(),
            type,
            status: statusCheck.checked ? "active" : "inactive",
          });
        } else {
          // Inject new card (reload page for simplicity; or inject via template)
          setTimeout(() => window.location.reload(), 800);
          return;
        }

        setTimeout(closeForm, 1000);
      } else {
        const fields = data.data?.fields || {};
        Object.entries(fields).forEach(([field, msg]) => {
          showFieldError(`ak-${field}-error`, msg);
        });
        setFormStatus(
          data.data?.message || cfg.i18n?.save_error || "Error saving key.",
          "error",
        );
      }
    } catch {
      setFormStatus(cfg.i18n?.save_error || "Network error.", "error");
    } finally {
      saving = false;
      saveBtn.disabled = false;
      saveLabel.textContent = "Save Key";
    }
  });

  // =========================================================================
  // Update card in DOM after edit
  // =========================================================================

  function updateCard(id, data) {
    const card = grid?.querySelector(`[data-id="${id}"]`);
    if (!card) return;

    card.dataset.type = data.type;
    card.dataset.status = data.status;
    card.dataset.name = data.name;

    const nameEl = card.querySelector(".ak-card__name");
    if (nameEl) nameEl.textContent = data.name;

    const typeBadge = card.querySelector(".ak-badge--type");
    if (typeBadge)
      typeBadge.textContent =
        data.type === "basic" ? "Basic Auth" : "Bearer Token";

    const iconEl = card.querySelector(".ak-card__icon");
    if (iconEl) iconEl.textContent = data.type === "basic" ? "🔑" : "🔐";

    const credLabel = card.querySelector(".ak-card__cred-label");
    if (credLabel)
      credLabel.textContent = data.type === "basic" ? "Credentials" : "Token";

    setCardStatus(card, data.status);
  }

  // =========================================================================
  // Edit button
  // =========================================================================

  grid?.addEventListener("click", function (e) {
    const editBtn = e.target.closest(".ak-edit-btn");
    if (!editBtn) return;

    const id = editBtn.dataset.id;
    const card = grid.querySelector(`[data-id="${id}"]`);
    if (!card) return;

    openForm("edit", {
      id: card.dataset.id,
      name: card.dataset.name,
      type: card.dataset.type,
      status: card.dataset.status,
    });
  });

  // =========================================================================
  // Delete button — show inline confirm
  // =========================================================================

  grid?.addEventListener("click", function (e) {
    const deleteBtn = e.target.closest(".ak-delete-btn");
    if (!deleteBtn) return;

    const id = deleteBtn.dataset.id;
    const confirm = document.getElementById(`ak-confirm-${id}`);
    if (confirm) {
      confirm.style.display = "flex";
      confirm.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
  });

  // Confirm YES — delete
  grid?.addEventListener("click", async function (e) {
    const yesBtn = e.target.closest(".ak-confirm-yes");
    if (!yesBtn || deleting) return;

    const id = yesBtn.dataset.id;
    deleting = true;
    yesBtn.textContent = cfg.i18n?.deleting || "Deleting…";
    yesBtn.disabled = true;

    const fd = new FormData();
    fd.append("action", "anyapi_delete_apikey");
    fd.append("nonce", cfg.nonce);
    fd.append("id", id);

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();

      if (data.success) {
        const card = grid.querySelector(`[data-id="${id}"]`);
        if (card) {
          card.classList.add("is-removing");
          setTimeout(() => {
            card.remove();
            checkEmpty();
          }, 300);
        }
        refreshUsageBar(data.data.count);
      }
    } catch {
      // silent fail — restore button
    } finally {
      deleting = false;
    }
  });

  // Confirm NO — cancel
  grid?.addEventListener("click", function (e) {
    const noBtn = e.target.closest(".ak-confirm-no");
    if (!noBtn) return;
    const id = noBtn.dataset.id;
    const confirm = document.getElementById(`ak-confirm-${id}`);
    if (confirm) confirm.style.display = "none";
  });

  // =========================================================================
  // Status toggle
  // =========================================================================

  grid?.addEventListener("change", async function (e) {
    const toggle = e.target.closest(".ak-status-toggle");
    if (!toggle) return;

    const id = toggle.dataset.id;
    const active = toggle.checked;
    const card = grid.querySelector(`[data-id="${id}"]`);

    const fd = new FormData();
    fd.append("action", "anyapi_toggle_apikey");
    fd.append("nonce", cfg.nonce);
    fd.append("id", id);
    fd.append("active", active ? "1" : "0");

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();
      if (data.success && card) {
        setCardStatus(card, data.data.status);
      } else {
        // Revert toggle on failure
        toggle.checked = !active;
      }
    } catch {
      toggle.checked = !active;
    }
  });

  // =========================================================================
  // Credential reveal toggle
  // =========================================================================

  // We don't expose raw credentials in the page DOM for security.
  grid?.addEventListener("click", function (e) {
    const revealBtn = e.target.closest(".ak-reveal-btn");
    if (!revealBtn) return;

    const id = revealBtn.dataset.id;
    const revealed = revealBtn.dataset.revealed === "1";
    const credValue = grid.querySelector(
      `.ak-card__cred-value[data-id="${id}"]`,
    );

    if (!credValue) return;

    if (revealed) {
      // Mask again
      credValue.textContent = "••••••••••••••••";
      revealBtn.textContent = "👁";
      revealBtn.dataset.revealed = "0";
    } else {
      // Show hint — real implementation would fetch via a secure AJAX endpoint
      credValue.textContent = "(edit key to view credentials)";
      revealBtn.textContent = "🙈";
      revealBtn.dataset.revealed = "1";
      // Auto-mask after 5s
      setTimeout(() => {
        if (revealBtn.dataset.revealed === "1") {
          credValue.textContent = "••••••••••••••••";
          revealBtn.textContent = "👁";
          revealBtn.dataset.revealed = "0";
        }
      }, 5000);
    }
  });

  // =========================================================================
  // Empty state check
  // =========================================================================

  function checkEmpty() {
    if (!grid) return;
    const cards = grid.querySelectorAll(".ak-card");
    let emptyEl = document.getElementById("ak-empty");

    if (cards.length === 0) {
      if (!emptyEl) {
        emptyEl = document.createElement("div");
        emptyEl.id = "ak-empty";
        emptyEl.className = "ak-empty";
        emptyEl.innerHTML = `
          <div class="ak-empty__icon">🔑</div>
          <h3 class="ak-empty__title">No API Keys yet</h3>
          <p class="ak-empty__desc">Create your first API Key to start connecting WooCommerce orders to external APIs.</p>
          <button class="ak-btn ak-btn--primary" id="ak-empty-add-btn" type="button">+ Add Your First Key</button>
        `;
        grid.appendChild(emptyEl);
        document
          .getElementById("ak-empty-add-btn")
          ?.addEventListener("click", handleAddClick);
      }
    } else if (emptyEl) {
      emptyEl.remove();
    }
  }
});
