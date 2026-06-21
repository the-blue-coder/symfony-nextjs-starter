"use client";

import { usePathname } from "@/lib/i18n";
import { useClerk } from "@clerk/nextjs";
import { LayoutDashboard } from "lucide-react";
import { useTranslations } from "next-intl";
import { useCallback, useState } from "react";

const useDashboardLayout = (initialCollapsed: boolean) => {
	const pathname = usePathname();
	const { signOut } = useClerk();
	const t = useTranslations("nav");

	const [isSidebarCollapsed, setIsSidebarCollapsedLocal] = useState(initialCollapsed);
	const [isMobileOpen, setIsMobileOpen] = useState(false);

	const showExpanded = !isSidebarCollapsed || isMobileOpen;

	const setIsSidebarCollapsed = useCallback((val: boolean) => {
		document.cookie = `sidebar-collapsed=${val ? "1" : ""}; path=/; max-age=${60 * 60 * 24 * 365}`;
		setIsSidebarCollapsedLocal(val);
	}, []);

	const toggleSidebar = useCallback(() => {
		setIsSidebarCollapsed(!isSidebarCollapsed);
	}, [isSidebarCollapsed, setIsSidebarCollapsed]);

	const resetMobileOpen = useCallback(() => {
		setIsMobileOpen(false);
	}, []);

	const toggleMobileSidebar = useCallback(() => {
		setIsMobileOpen((prev) => !prev);
	}, []);

	const isNavActive = useCallback(
		(href: string) => {
			if (href === "/dashboard") {
				return pathname === "/dashboard";
			}
			return pathname === href || pathname.startsWith(href + "/");
		},
		[pathname]
	);

	const handleSignOut = useCallback(async () => {
		await signOut({ redirectUrl: "/" });
	}, [signOut]);

	return {
		isMobileOpen,
		isSidebarCollapsed,
		navItems: [{ href: "/dashboard", label: t("dashboard"), icon: LayoutDashboard }],
		settingsLabel: t("settings"),
		pathname,
		showExpanded,
		handleSignOut,
		isNavActive,
		resetMobileOpen,
		toggleMobileSidebar,
		toggleSidebar,
	};
};

export default useDashboardLayout;
