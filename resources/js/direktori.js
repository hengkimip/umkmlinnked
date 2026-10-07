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
 * Beranda "Semua Brand": seluruh UMKM tampil tepat sekali, 4 kartu per baris sampai ke bawah.
 * Baris yang terlihat berganti acak dengan bertukar kartu dengan baris lain yang sedang
 * di luar layar — jadi tidak ada UMKM yang hilang atau tampil dobel.
 * - hanya berjalan saat baris terlihat di layar & tab aktif (hemat CPU/kuota)
 * - berhenti saat kursor/fokus keyboard di baris, atau tombol jeda ditekan (WCAG 2.2.2)
 * - gambar kartu berikutnya dimuat dulu agar pergantian tidak berkedip
 */
function initRotasiProduk() {
    const JEDA = 5500; // ms antar-pergantian per baris
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
    // Tukar posisi dua kartu di DOM (boleh beda baris)
    const tukar = (a, b) => {
        const penanda = document.createComment("");
        a.replaceWith(penanda);
        b.replaceWith(a);
        penanda.replaceWith(b);
    };

    document.querySelectorAll("[data-rotasi-grup]").forEach((grup) => {
        const tombol = grup.querySelector("[data-rotasi-jeda]");
        const baris = [...grup.querySelectorAll("[data-rotasi-baris]")].map((ul) => ({
            ul,
            terlihat: false,
            disentuh: false,
        }));
        if (baris.length < 2) return;

        let dijeda = false;
        let sibuk = false; // satu pergantian sekaligus agar kartu tidak diperebutkan

        const bolehJalan = (b) => !dijeda && !b.disentuh && b.terlihat && !document.hidden;

        const ganti = async (b) => {
            if (sibuk || !bolehJalan(b)) return;

            // Sumber kartu: baris lain yang di luar layar; bila semua terlihat, baris lain yang tidak disentuh
            const lain = baris.filter((x) => x !== b && !x.disentuh);
            const sumber = lain.some((x) => !x.terlihat) ? lain.filter((x) => !x.terlihat) : lain;
            const kolam = sumber.flatMap((x) => [...x.ul.children]);
            if (!kolam.length) return;

            sibuk = true;
            const lama = [...b.ul.children];
            const baru = acak(kolam).slice(0, Math.min(lama.length, kolam.length));
            await Promise.all(baru.map(muatGambar));

            if (bolehJalan(b)) {
                await Promise.all(
                    baru.map(
                        (liBaru, i) =>
                            new Promise((selesai) =>
                                setTimeout(() => {
                                    lama[i].classList.add("is-keluar");
                                    setTimeout(() => {
                                        liBaru.classList.add("is-masuk");
                                        tukar(lama[i], liBaru);
                                        lama[i].classList.remove("is-keluar");
                                        requestAnimationFrame(() =>
                                            requestAnimationFrame(() => liBaru.classList.remove("is-masuk")),
                                        );
                                        selesai();
                                    }, 320);
                                }, i * 110), // bergeser satu per satu, kiri ke kanan
                            ),
                    ),
                );
            }
            sibuk = false;
        };

        // Hanya berputar saat baris tampil di layar
        const pengamat = new IntersectionObserver(
            (entri) =>
                entri.forEach((e) => {
                    const b = baris.find((x) => x.ul === e.target);
                    if (b) b.terlihat = e.isIntersecting;
                }),
            { threshold: 0.35 },
        );

        baris.forEach((b) => {
            pengamat.observe(b.ul);
            ["mouseenter", "focusin"].forEach((ev) => b.ul.addEventListener(ev, () => (b.disentuh = true)));
            b.ul.addEventListener("mouseleave", () => (b.disentuh = false));
            b.ul.addEventListener("focusout", (e) => {
                if (!b.ul.contains(e.relatedTarget)) b.disentuh = false;
            });
            // Baris tidak berganti serentak
            setTimeout(() => setInterval(() => ganti(b), JEDA), Number(b.ul.dataset.rotasiJedaAwal || 0));
        });

        tombol?.addEventListener("click", () => {
            dijeda = !dijeda;
            tombol.setAttribute("aria-pressed", String(dijeda));
            tombol.setAttribute(
                "aria-label",
                (dijeda ? "Lanjutkan" : "Jeda") + tombol.getAttribute("aria-label").replace(/^(Jeda|Lanjutkan)/, ""),
            );
        });
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
