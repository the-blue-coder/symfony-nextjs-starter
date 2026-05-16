"use client";

import { usePathname } from "@/lib/i18n";
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

	return { isNavActive, isSidebarOpen, handleSignOut, toggleSidebar };
};

export default useDashboardLayout;
