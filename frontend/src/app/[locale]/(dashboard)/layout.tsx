import { cookies } from "next/headers";
import DashboardLayoutClient from "./DashboardLayoutClient";

const DashboardLayout = async ({ children }: { children: React.ReactNode }) => {
	const jar = await cookies();
	const collapsed = jar.get("sidebar-collapsed")?.value === "1";

	return <DashboardLayoutClient initialCollapsed={collapsed}>{children}</DashboardLayoutClient>;
};

export default DashboardLayout;
