"use client";

import useQueryProvider from "./hooks/useQueryProvider";
import { QueryClientProvider } from "@tanstack/react-query";

const QueryProvider: React.FC<TQueryProviderProps> = ({ children }) => {
	const { queryClient } = useQueryProvider();

	return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
};

type TQueryProviderProps = {
	children: React.ReactNode;
};

export default QueryProvider;
