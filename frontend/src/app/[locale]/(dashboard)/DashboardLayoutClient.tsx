"use client";

import { useEffect } from "react";
import { ChevronLeft, ChevronRight, LayoutDashboard, LogOut, Menu, Settings } from "lucide-react";
import { Link } from "@/lib/i18n";
import { Button } from "@/components/ui/button";
import { APP_NAME } from "@/constants/app";
import useDashboardLayout from "./hooks/useDashboardLayout";

const DashboardLayoutClient: React.FC<TDashboardLayoutClientProps> = ({ initialCollapsed, children }) => {
	const {
		isMobileOpen,
		isSidebarCollapsed,
		pathname,
		showExpanded,
		handleSignOut,
		isNavActive,
		resetMobileOpen,
		toggleMobileSidebar,
		toggleSidebar,
	} = useDashboardLayout(initialCollapsed);

	// close mobile sidebar on route change
	useEffect(() => {
		resetMobileOpen();
	}, [pathname, resetMobileOpen]);

	return (
		<div className="flex min-h-screen bg-background">
			{/* Mobile overlay */}
			{isMobileOpen && <div onClick={toggleMobileSidebar} className="fixed inset-0 bg-black/50 z-40 lg:hidden" />}

			<aside
				className={`shrink-0 flex flex-col border-r bg-background fixed top-0 left-0 h-full z-50 transition-[width,transform] duration-200 overflow-hidden w-60 ${isSidebarCollapsed ? "lg:w-14" : "lg:w-60"} ${isMobileOpen ? "translate-x-0" : "-translate-x-full lg:translate-x-0"}`}
			>
				{/* Header */}
				{!showExpanded ? (
					<div className="h-16 shrink-0 flex items-center justify-center border-b">
						<Link href={navItems[0].href}>
							<img src="/icon.svg" alt={APP_NAME} className="h-8 w-8 rounded-md" />
						</Link>
					</div>
				) : (
					<div className="h-16 shrink-0 border-b flex items-center px-4">
						<Link href={navItems[0].href} className="flex items-center gap-2 flex-1 min-w-0">
							<img src="/icon.svg" alt={APP_NAME} className="h-7 w-7 rounded-md shrink-0" />
							<span className="font-semibold text-sm truncate">{APP_NAME}</span>
						</Link>
						<button
							onClick={toggleSidebar}
							title="Collapse sidebar"
							className="hidden lg:flex items-center justify-center w-7 h-7 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent cursor-pointer transition-colors shrink-0 ml-2"
						>
							<ChevronLeft size={15} />
						</button>
					</div>
				)}

				{/* Nav */}
				{showExpanded ? (
					<nav className="flex-1 p-3 space-y-1">
						{navItems.map(({ href, label, icon: Icon }) => (
							<Link
								key={href}
								href={href}
								className={`flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors ${
									isNavActive(href) ? "bg-accent text-accent-foreground font-medium" : "text-muted-foreground hover:bg-accent hover:text-accent-foreground"
								}`}
							>
								<Icon size={16} />
								{label}
							</Link>
						))}
					</nav>
				) : (
					<nav className="flex-1 flex flex-col items-center py-3 gap-1">
						<button
							onClick={toggleSidebar}
							title="Expand sidebar"
							className="flex items-center justify-center w-9 h-9 rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent cursor-pointer transition-colors"
						>
							<ChevronRight size={16} />
						</button>
						{navItems.map(({ href, label, icon: Icon }) => (
							<Link
								key={href}
								href={href}
								title={label}
								className={`flex items-center justify-center w-9 h-9 rounded-lg cursor-pointer transition-colors ${
									isNavActive(href) ? "bg-accent text-accent-foreground" : "text-muted-foreground hover:text-foreground hover:bg-accent"
								}`}
							>
								<Icon size={18} />
							</Link>
						))}
					</nav>
				)}

				{/* Footer */}
				<div className={`border-t py-3 ${showExpanded ? "px-3" : "flex flex-col items-center"}`}>
					{showExpanded ? (
						<Button variant="ghost" size="sm" className="w-full justify-start gap-3 cursor-pointer" onClick={handleSignOut}>
							<LogOut size={16} />
							Sign out
						</Button>
					) : (
						<button
							title="Sign out"
							onClick={handleSignOut}
							className="flex items-center justify-center w-9 h-9 rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent cursor-pointer transition-colors"
						>
							<LogOut size={18} />
						</button>
					)}
				</div>
			</aside>

			{/* Main */}
			<main className={`flex-1 min-h-screen transition-[margin] duration-200 ${isSidebarCollapsed ? "lg:ml-14" : "lg:ml-60"}`}>
				{/* Mobile top bar */}
				<div className="lg:hidden sticky top-0 z-30 flex items-center gap-3 px-4 h-14 bg-background border-b">
					<button
						onClick={toggleMobileSidebar}
						className="flex items-center justify-center w-9 h-9 rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent cursor-pointer transition-colors"
					>
						<Menu size={20} />
					</button>
					<Link href={navItems[0].href} className="flex items-center gap-2">
						<img src="/icon.svg" alt={APP_NAME} className="h-6 w-6 rounded-md" />
						<span className="font-semibold text-sm">{APP_NAME}</span>
					</Link>
				</div>
				<div className="p-6">{children}</div>
			</main>
		</div>
	);
};

const navItems = [
	{ href: "/dashboard", label: "Dashboard", icon: LayoutDashboard },
	{ href: "/settings", label: "Settings", icon: Settings },
];

type TDashboardLayoutClientProps = {
	initialCollapsed: boolean;
	children: React.ReactNode;
};

export default DashboardLayoutClient;
