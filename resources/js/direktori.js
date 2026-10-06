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
    // Kotak centang banyak pilihan (Semua Brand): ?platform=shopee,tiktok
    document.querySelectorAll("[data-filter-multi]").forEach((input) => {
        input.addEventListener("change", function () {
            const key = this.dataset.filterMulti;
            const url = new URL(window.location.href);
            const pilihan = new Set((url.searchParams.get(key) || "").split(",").filter(Boolean));
            if (this.checked) pilihan.add(this.dataset.filterValue);
            else pilihan.delete(this.dataset.filterValue);
            url.searchParams.delete("page");
            if (pilihan.size) url.searchParams.set(key, [...pilihan].join(","));
            else url.searchParams.delete(key);
            window.location.href = url.toString();
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
    const desc = gallery.querySelector("[data-gallery-desc]");
    const badge = gallery.querySelector("[data-gallery-badge]");
    const thumbs = [...gallery.querySelectorAll("[data-thumb-src]")];
    const count = gallery.querySelector("[data-gallery-count]");
    let aktif = Math.max(0, thumbs.findIndex((t) => t.getAttribute("aria-pressed") === "true"));

    // Panah kiri/kanan, tombol panah papan ketik, dan geser (swipe) di layar sentuh
    const geser = (arah) => {
        if (thumbs.length < 2) return;
        const t = thumbs[(aktif + arah + thumbs.length) % thumbs.length];
        t.click();
        // Gulir deret thumbnail secara horizontal saja (halaman tidak ikut bergeser)
        const wadah = t.parentElement;
        const rT = t.getBoundingClientRect();
        const rW = wadah.getBoundingClientRect();
        wadah.scrollBy({ left: rT.left - rW.left - (rW.width - rT.width) / 2, behavior: "smooth" });
    };
    gallery.querySelector("[data-gallery-prev]")?.addEventListener("click", () => geser(-1));
    gallery.querySelector("[data-gallery-next]")?.addEventListener("click", () => geser(1));
    stage.addEventListener("keydown", (e) => {
        if (e.key === "ArrowLeft") geser(-1);
        if (e.key === "ArrowRight") geser(1);
    });
    let mulaiX = null;
    stage.addEventListener("touchstart", (e) => (mulaiX = e.touches[0].clientX), { passive: true });
    stage.addEventListener("touchend", (e) => {
        if (mulaiX === null) return;
        const dx = e.changedTouches[0].clientX - mulaiX;
        mulaiX = null;
        if (Math.abs(dx) > 40) geser(dx < 0 ? 1 : -1);
    }, { passive: true });

    thumbs.forEach((thumb, i) => {
        thumb.addEventListener("click", () => {
            aktif = i;
            if (count) count.textContent = `${i + 1} / ${thumbs.length}`;
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
            if (desc) {
                desc.textContent = thumb.dataset.thumbDesc || "";
                desc.hidden = !thumb.dataset.thumbDesc;
            }
            if (badge) {
                const kode = thumb.dataset.thumbBadge || "";
                badge.textContent = thumb.dataset.thumbBadgeLabel || "";
                badge.className = "ib-badge" + (/^[a-z]+$/.test(kode) ? ` ib-badge--${kode}` : "");
                badge.hidden = !thumb.dataset.thumbBadgeLabel;
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

/**
 * Beranda: 4 kartu per baris berganti acak dari kartu cadangan (<template>).
 * - hanya berjalan saat baris terlihat di layar & tab aktif (hemat CPU/kuota)
 * - berhenti saat kursor/fokus keyboard di baris, atau tombol jeda ditekan (WCAG 2.2.2)
 * - gambar kartu berikutnya dimuat dulu agar pergantian tidak berkedip
 */
function initRotasiProduk() {
    const JEDA = 5500; // ms antar-pergantian
    const acak = (daftar) => {
        const a = [...daftar];
        for (let i = a.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [a[i], a[j]] = [a[j], a[i]];
        }
        return a;
    };
    const muatGambar = (li) =>
        new Promise((selesai) => {
            const src = li.querySelector("img")?.getAttribute("src");
            if (!src) return selesai();
            const img = new Image();
            img.onload = img.onerror = selesai;
            img.src = src;
            setTimeout(selesai, 4000);
        });

    document.querySelectorAll("[data-rotasi]").forEach((grid) => {
        const rail = grid.closest(".ib-rail");
        const template = rail?.querySelector("template[data-rotasi-cadangan]");
        const tombol = rail?.querySelector("[data-rotasi-jeda]");
        if (!template) return;

        let tampil = [...grid.children];
        let cadangan = [...template.content.children].map((n) => n.cloneNode(true));
        let dijeda = false;
        let disentuh = false;
        let terlihat = false;
        let sibuk = false;
        let timer = null;

        const bolehJalan = () => !dijeda && !disentuh && terlihat && !document.hidden;

        const ganti = async () => {
            if (sibuk || !bolehJalan() || !cadangan.length) return;
            sibuk = true;
            const baru = acak(cadangan).slice(0, Math.min(tampil.length, cadangan.length));
            await Promise.all(baru.map(muatGambar));

            if (bolehJalan()) {
                const lama = tampil.slice(0, baru.length);
                baru.forEach((liBaru, i) => {
                    setTimeout(() => {
                        lama[i].classList.add("is-keluar");
                        setTimeout(() => {
                            liBaru.classList.remove("is-keluar");
                            liBaru.classList.add("is-masuk");
                            grid.replaceChild(liBaru, lama[i]);
                            requestAnimationFrame(() =>
                                requestAnimationFrame(() => liBaru.classList.remove("is-masuk")),
                            );
                        }, 320);
                    }, i * 110); // bergeser satu per satu, kiri ke kanan
                });
                cadangan = cadangan.filter((li) => !baru.includes(li)).concat(lama);
                tampil = baru.concat(tampil.slice(baru.length));
            }
            sibuk = false;
        };

        const mulai = () => {
            clearInterval(timer);
            timer = setInterval(ganti, JEDA);
        };

        // Hanya berputar saat baris tampil di layar
        new IntersectionObserver(
            ([entri]) => {
                terlihat = entri.isIntersecting;
            },
            { threshold: 0.35 },
        ).observe(grid);

        ["mouseenter", "focusin"].forEach((ev) => rail.addEventListener(ev, () => (disentuh = true)));
        rail.addEventListener("mouseleave", () => (disentuh = false));
        rail.addEventListener("focusout", (e) => {
            if (!rail.contains(e.relatedTarget)) disentuh = false;
        });

        tombol?.addEventListener("click", () => {
            dijeda = !dijeda;
            tombol.setAttribute("aria-pressed", String(dijeda));
            tombol.setAttribute(
                "aria-label",
                (dijeda ? "Lanjutkan" : "Jeda") + tombol.getAttribute("aria-label").replace(/^(Jeda|Lanjutkan)/, ""),
            );
        });

        // Baris tidak berganti serentak
        setTimeout(mulai, Number(grid.dataset.rotasiJedaAwal || 0));
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initRotasiProduk();
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
