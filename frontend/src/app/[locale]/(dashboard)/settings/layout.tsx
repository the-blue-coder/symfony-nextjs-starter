import { getTranslations } from "next-intl/server";
import SettingsLayoutClient from "./SettingsLayoutClient";

export const generateMetadata = async () => {
	const t = await getTranslations("nav");
	return { title: t("settings") };
};

const SettingsLayout: React.FC<TSettingsLayoutProps> = ({ children }) => {
	return <SettingsLayoutClient>{children}</SettingsLayoutClient>;
};

type TSettingsLayoutProps = {
	children: React.ReactNode;
};

export default SettingsLayout;
