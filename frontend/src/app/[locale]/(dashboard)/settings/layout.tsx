"use client";

import { Link } from "@/lib/i18n";
import useSettingsLayout from "./hooks/useSettingsLayout";

const SettingsLayout: React.FC<TSettingsLayoutProps> = ({ children }) => {
	const { isNavActive, navItems } = useSettingsLayout();

	return (
		<div className="flex gap-6">
			<aside className="w-48 shrink-0">
				<nav className="space-y-1">
					{navItems.map(({ href, label }) => (
						<Link
							key={href}
							href={href}
							className={`block rounded-md px-3 py-2 text-sm transition-colors ${
								isNavActive(href)
									? "bg-accent text-accent-foreground font-medium"
									: "text-muted-foreground hover:bg-accent hover:text-accent-foreground"
							}`}
						>
							{label}
						</Link>
					))}
				</nav>
			</aside>
			<div className="flex-1">{children}</div>
		</div>
	);
};

type TSettingsLayoutProps = {
	children: React.ReactNode;
};

export default SettingsLayout;
