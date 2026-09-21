export interface MegaMenuLinkSetting {
  enabled?: boolean;
  label?: string | null;
  url?: string | null;
}

export interface MegaMenuColumnSetting {
  enabled?: boolean;
  title?: string | null;
  links?: MegaMenuLinkSetting[];
}

export interface MegaMenuSetting {
  enabled?: boolean;
  top_choices?: {
    enabled?: boolean;
    eyebrow?: string | null;
    links?: MegaMenuLinkSetting[];
  };
  columns?: MegaMenuColumnSetting[];
  promo?: {
    enabled?: boolean;
    image?: string | null;
    alt?: string | null;
    label?: string | null;
    url?: string | null;
  };
}

export interface HeaderNavigationItemSetting {
  enabled?: boolean;
  label?: string | null;
  url?: string | null;
  target?: '_self' | '_blank' | string | null;
  mega_menu?: MegaMenuSetting;
}

export function isUsableNavigationLink(link: MegaMenuLinkSetting): boolean {
  return link.enabled !== false && Boolean(link.label?.trim() && link.url?.trim());
}
