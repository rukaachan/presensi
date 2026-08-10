import "./app-support";
import "@phosphor-icons/web/bold";

document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
    const toggle = document.querySelector("[data-sidebar-toggle]");
    const closeTargets = document.querySelectorAll("[data-sidebar-close], .sidebar-link");

    const setSidebarState = (isOpen) => {
        body.classList.toggle("sidebar-open", isOpen);
        toggle?.setAttribute("aria-expanded", String(isOpen));
        toggle?.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
    };

    if (toggle) {
        toggle.addEventListener("click", () => {
            setSidebarState(!body.classList.contains("sidebar-open"));
        });

        closeTargets.forEach((target) => {
            target.addEventListener("click", () => setSidebarState(false));
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                setSidebarState(false);
            }
        });
    }

    document.querySelectorAll("#kembali").forEach((button) => {
        button.addEventListener("click", () => window.history.back());
    });

    document.querySelectorAll(".filter, #filter_bulan, #filter_tanggal").forEach((control) => {
        control.addEventListener("change", () => {
            const month = document.getElementById("filter_bulan");
            const date = document.getElementById("filter_tanggal");

            if (control === month && date) {
                date.disabled = Boolean(month.value);
            }

            if (control === date && month) {
                month.disabled = Boolean(date.value);
            }

            control.form?.requestSubmit();
        });
    });

    document.addEventListener("click", async (event) => {
        const target = event.target instanceof Element ? event.target : null;
        const trigger = target?.closest("[data-delete-url]");

        if (!trigger || trigger.disabled) {
            return;
        }

        event.preventDefault();

        const confirmation = await window.swal.fire({
            title: trigger.dataset.deleteTitle || "Hapus data ini?",
            text: trigger.dataset.deleteMessage || "Data yang dihapus tidak dapat dipulihkan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Hapus data",
            cancelButtonText: "Batal",
            reverseButtons: true,
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        trigger.disabled = true;
        const payload = new URLSearchParams();
        payload.set("_token", document.querySelector('meta[name="csrf-token"]')?.content || "");
        payload.set(trigger.dataset.deleteField || "id", trigger.dataset.deleteId || "");

        try {
            const response = await window.axios.delete(trigger.dataset.deleteUrl, { data: payload });

            if (!response.data?.success) {
                throw new Error("Delete request was not accepted");
            }

            await window.swal.fire({
                title: "Data dihapus",
                text: "Ruang kerja akan diperbarui.",
                icon: "success",
                timer: 1200,
                showConfirmButton: false,
            });
            window.location.reload();
        } catch (error) {
            trigger.disabled = false;
            window.swal.fire({
                title: "Data belum dihapus",
                text: "Server tidak menerima perubahan ini. Silakan coba lagi.",
                icon: "error",
                confirmButtonText: "Mengerti",
            });
        }
    });
});
