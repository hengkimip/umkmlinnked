/**
 * Modul untuk halaman direktori (Inkubator Bank Indonesia)
 */

let ibSliderPos = 0;

function initSlider() {
    document.querySelectorAll("[data-slider-prev]").forEach((btn) => {
        btn.addEventListener("click", () => slideTrack(-1));
    });
    document.querySelectorAll("[data-slider-next]").forEach((btn) => {
        btn.addEventListener("click", () => slideTrack(1));
    });
}

function slideTrack(direction) {
    const track = document.getElementById("trending-track");
    if (!track || !track.children.length) return;
    const cardWidth = track.children[0].offsetWidth + 16;
    const visible =
        Math.floor(track.parentElement.offsetWidth / cardWidth) || 1;
    const max = Math.max(0, (track.children.length - visible) * cardWidth);
    ibSliderPos = Math.max(
        0,
        Math.min(ibSliderPos + direction * cardWidth, max),
    );
    track.style.transform = `translateX(-${ibSliderPos}px)`;
}

function initFilterCheckboxes() {
    document.querySelectorAll("[data-filter-key]").forEach((input) => {
        input.addEventListener("change", function () {
            applyFilter(
                this.dataset.filterKey,
                this.dataset.filterValue,
                this.checked,
            );
        });
    });
    document.querySelectorAll("[data-filter-select]").forEach((select) => {
        select.addEventListener("change", function () {
            applyFilter(this.dataset.filterSelect, this.value, !!this.value);
        });
    });
}

function applyFilter(key, value, active) {
    const url = new URL(window.location.href);
    url.searchParams.delete("page");
    if (active && value) {
        url.searchParams.set(key, value);
    } else {
        url.searchParams.delete(key);
    }
    window.location.href = url.toString();
}

function initHargaFilter() {
    const btn = document.getElementById("apply-harga-btn");
    if (!btn) return;
    btn.addEventListener("click", () => {
        const min = document.getElementById("harga_min")?.value;
        const max = document.getElementById("harga_max")?.value;
        const url = new URL(window.location.href);
        url.searchParams.delete("page");
        min
            ? url.searchParams.set("harga_min", min)
            : url.searchParams.delete("harga_min");
        max
            ? url.searchParams.set("harga_max", max)
            : url.searchParams.delete("harga_max");
        window.location.href = url.toString();
    });
}

function initWhatsappButtons() {
    document.querySelectorAll("[data-wa-link]").forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.open(this.dataset.waLink, "_blank");
        });
    });
}

function initResetFilter() {
    document.querySelectorAll("[data-reset-filter]").forEach((btn) => {
        btn.addEventListener("click", () => {
            window.location.href = window.location.pathname;
        });
    });
}

function initMobileFilterToggle() {
    const toggle = document.getElementById("mobile-filter-toggle");
    const panel = document.getElementById("sidebar-panel");
    if (!toggle || !panel) return;
    toggle.addEventListener("click", () => {
        panel.classList.toggle("is-open");
        const isOpen = panel.classList.contains("is-open");
        toggle.querySelector("[data-toggle-icon]").textContent = isOpen
            ? "▲"
            : "▼";
    });
}

function initMobileNav() {
    const burger = document.getElementById("nav-burger");
    const menu = document.getElementById("nav-menu");
    if (!burger || !menu) return;
    burger.addEventListener("click", () => {
        menu.classList.toggle("is-open");
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initSlider();
    initFilterCheckboxes();
    initHargaFilter();
    initWhatsappButtons();
    initResetFilter();
    initMobileFilterToggle();
    initMobileNav();
});
