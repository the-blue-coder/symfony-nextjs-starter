"use client";

import { usePathname } from "@/i18n/navigation";
import { useClerk } from "@clerk/nextjs";
import useUIStore from "@/store/useUIStore";

const useDashboardLayout = () => {
	const pathname = usePathname();
	const { signOut } = useClerk();
	const { isSidebarOpen, toggleSidebar } = useUIStore();

	const isNavActive = (href: string) => {
		if (href === "/dashboard") return pathname === "/dashboard";
		return pathname.startsWith(href);
	};

	const handleSignOut = async () => {
		await signOut({ redirectUrl: "/" });
	};

	return { isSidebarOpen, isNavActive, toggleSidebar, handleSignOut };
};

export default useDashboardLayout;
