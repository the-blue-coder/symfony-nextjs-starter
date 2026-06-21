import { getTranslations } from "next-intl/server";

export const generateMetadata = async () => {
	const t = await getTranslations("nav");
	return { title: t("dashboard") };
};

const DashboardSectionLayout: React.FC<TDashboardSectionLayoutProps> = ({ children }) => {
	return children;
};

type TDashboardSectionLayoutProps = {
	children: React.ReactNode;
};

export default DashboardSectionLayout;
