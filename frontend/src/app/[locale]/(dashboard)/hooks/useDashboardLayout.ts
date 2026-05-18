"use client";

import { usePathname } from "@/lib/i18n";
import { useClerk } from "@clerk/nextjs";
import { useCallback, useEffect, useState } from "react";

const useDashboardLayout = (initialCollapsed: boolean) => {
	const pathname = usePathname();
	const { signOut } = useClerk();

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

	const toggleMobileSidebar = useCallback(() => {
		setIsMobileOpen((prev) => !prev);
	}, []);

	useEffect(() => {
		setIsMobileOpen(false);
	}, [pathname]);

	const isNavActive = useCallback(
		(href: string) => {
			if (href === "/dashboard") return pathname === "/dashboard";
			return pathname === href || pathname.startsWith(href + "/");
		},
		[pathname]
	);

	const handleSignOut = useCallback(async () => {
		await signOut({ redirectUrl: "/" });
	}, [signOut]);

	return { isSidebarCollapsed, isMobileOpen, showExpanded, isNavActive, handleSignOut, toggleSidebar, toggleMobileSidebar };
};

export default useDashboardLayout;
