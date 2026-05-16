"use client";

import { QueryClient } from "@tanstack/react-query";
import { useState } from "react";

const useQueryProvider = () => {
	const [queryClient] = useState(
		() =>
			new QueryClient({
				defaultOptions: {
					queries: {
						staleTime: 60 * 1000,
						retry: 1,
					},
				},
			})
	);

	return { queryClient };
};

export default useQueryProvider;
