"use client";

import useDashboardLayout from "./hooks/useDashboardLayout";
import { Link } from "@/i18n/navigation";
import { LayoutDashboard, Settings, LogOut, Menu } from "lucide-react";
import { Button } from "@/components/ui/button";

const DashboardLayout: React.FC<TDashboardLayoutProps> = ({ children }) => {
	const { isNavActive, isSidebarOpen, handleSignOut, toggleSidebar } = useDashboardLayout();

	return (
		<div className="flex h-screen overflow-hidden">
			{/* Sidebar */}
			<aside className={`flex flex-col border-r bg-background transition-all duration-200 ${isSidebarOpen ? "w-60" : "w-0 overflow-hidden"}`}>
				<div className="flex h-16 items-center px-4 border-b">
					<span className="font-semibold text-sm">[Project Name]</span>
				</div>

				<nav className="flex-1 p-3 space-y-1">
					{navItems.map(({ href, label, icon: Icon }) => (
						<Link
							key={href}
							href={href}
							className={`flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors cursor-pointer ${
								isNavActive(href) ? "bg-accent text-accent-foreground font-medium" : "text-muted-foreground hover:bg-accent hover:text-accent-foreground"
							}`}
						>
							<Icon size={16} />
							{label}
						</Link>
					))}
				</nav>

				<div className="p-3 border-t">
					<Button variant="ghost" size="sm" className="w-full justify-start gap-3 cursor-pointer" onClick={handleSignOut}>
						<LogOut size={16} />
						Sign out
					</Button>
				</div>
			</aside>

			{/* Main */}
			<div className="flex flex-1 flex-col overflow-hidden">
				<header className="flex h-16 items-center gap-4 border-b px-4">
					<Button variant="ghost" size="icon" className="cursor-pointer" onClick={toggleSidebar}>
						<Menu size={18} />
					</Button>
				</header>

				<main className="flex-1 overflow-auto p-6">{children}</main>
			</div>
		</div>
	);
};

const navItems = [
	{ href: "/dashboard", label: "Dashboard", icon: LayoutDashboard },
	{ href: "/dashboard/settings", label: "Settings", icon: Settings },
];

type TDashboardLayoutProps = {
	children: React.ReactNode;
};

export default DashboardLayout;
