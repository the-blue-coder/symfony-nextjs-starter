import { create } from "zustand";

const useUIStore = create<TUIStore>((set) => ({
	isSidebarOpen: true,
	theme: "light",
	setSidebarOpen: (open) => set({ isSidebarOpen: open }),
	toggleSidebar: () => set((state) => ({ isSidebarOpen: !state.isSidebarOpen })),
	setTheme: (theme) => set({ theme }),
}));

type TTheme = "light" | "dark";

type TUIStore = {
	isSidebarOpen: boolean;
	theme: TTheme;
	setSidebarOpen: (open: boolean) => void;
	toggleSidebar: () => void;
	setTheme: (theme: TTheme) => void;
};

export default useUIStore;
