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
    const gap = parseFloat(getComputedStyle(track).columnGap) || 16;
    const cardWidth = track.children[0].offsetWidth + gap;
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
            // Hanya izinkan tautan wa.me; noopener agar tab baru tak bisa mengakses window.opener
            const link = this.dataset.waLink || "";
            if (/^https:\/\/wa\.me\/\d{8,15}$/.test(link)) {
                window.open(link, "_blank", "noopener,noreferrer");
            }
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
        const isOpen = panel.classList.toggle("is-open");
        toggle.setAttribute("aria-expanded", String(isOpen));
    });
}

function initMobileNav() {
    const burger = document.getElementById("nav-burger");
    const menu = document.getElementById("nav-menu");
    if (!burger || !menu) return;
    const setOpen = (open) => {
        menu.classList.toggle("is-open", open);
        burger.setAttribute("aria-expanded", String(open));
        burger.setAttribute("aria-label", open ? "Tutup menu" : "Buka menu");
    };
    burger.addEventListener("click", () =>
        setOpen(!menu.classList.contains("is-open")),
    );
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") setOpen(false);
    });
}

// URL gambar aman untuk dipasang ke src & CSS url() (sama dengan aturan di server)
const SAFE_IMG = /^(https?:\/\/|\/)[^\s'"()<>\\]+$/;

function initGallery() {
    const gallery = document.querySelector("[data-gallery]");
    if (!gallery) return;
    const stage = gallery.querySelector("[data-gallery-stage]");
    const img = gallery.querySelector("[data-gallery-img]");
    const name = gallery.querySelector("[data-gallery-name]");
    const price = gallery.querySelector("[data-gallery-price]");
    const wa = gallery.querySelector("[data-gallery-wa]");
    const thumbs = gallery.querySelectorAll("[data-thumb-src]");

    thumbs.forEach((thumb) => {
        thumb.addEventListener("click", () => {
            const src = thumb.dataset.thumbSrc;
            if (!img || !SAFE_IMG.test(src)) return;
            img.src = src;
            img.alt = thumb.dataset.thumbName || "";
            stage.style.setProperty("--img", `url('${src}')`);
            if (name) name.textContent = thumb.dataset.thumbName || "";
            if (price) {
                price.textContent = "";
                if (thumb.dataset.thumbPrice) {
                    const strong = document.createElement("strong");
                    strong.textContent = thumb.dataset.thumbPrice;
                    price.appendChild(strong);
                } else {
                    price.textContent = "Harga dapat ditanyakan langsung ke penjual";
                }
            }
            if (wa && /^https:\/\/wa\.me\/\d{8,15}\?text=/.test(thumb.dataset.thumbWa || "")) {
                wa.href = thumb.dataset.thumbWa;
            }
            thumbs.forEach((t) => t.setAttribute("aria-pressed", String(t === thumb)));
        });
    });
}

function initShare() {
    document.querySelectorAll("[data-share]").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const url = btn.dataset.shareUrl || window.location.href;
            const title = btn.dataset.shareTitle || document.title;
            const label = btn.querySelector("[data-share-label]");
            try {
                if (navigator.share) {
                    await navigator.share({ title, url });
                    return;
                }
                await navigator.clipboard.writeText(url);
                if (label) {
                    label.textContent = "Tautan disalin";
                    setTimeout(() => (label.textContent = "Bagikan"), 2000);
                }
            } catch (e) {
                /* dibatalkan pengguna */
            }
        });
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initGallery();
    initShare();
    initSlider();
    initFilterCheckboxes();
    initHargaFilter();
    initWhatsappButtons();
    initResetFilter();
    initMobileFilterToggle();
    initMobileNav();
});
