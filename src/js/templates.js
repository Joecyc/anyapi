/**
 * templates.js
 *
 * Integration Templates page: card interactions, variant toggle, upgrade modal.
 */

document.addEventListener("DOMContentLoaded", function () {
  const page = document.getElementById("anyapi-templates-page");
  if (!page) return;

  // ===========================================================================
  // Upgrade modal
  // ===========================================================================

  const upgradeModal = document.getElementById("anyapi-upgrade-modal");
  const modalBody = document.getElementById("modal-body");
  const modalCloseBtn = upgradeModal?.querySelector(".upgrade-modal__close");
  const modalBackdrop = upgradeModal?.querySelector(".upgrade-modal__backdrop");

  function openUpgradeModal(message) {
    if (!upgradeModal) return;
    if (modalBody) modalBody.textContent = message || "";
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
  // Card interactions
  // ===========================================================================

  document.querySelectorAll(".tpl-card").forEach((card) => {
    card.addEventListener("click", () => {
      if (card.dataset.tpl === "email") {
        document.dispatchEvent(
          new CustomEvent("anyapi:open-template", {
            detail: { tplId: "email", variant: "default" },
          }),
        );
      } else {
        openUpgradeModal(card.dataset.lockMessage);
      }
    });
  });
});
