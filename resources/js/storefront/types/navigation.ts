export interface NavigationItem {
  label: string;
  url: string;
  target?: '_self' | '_blank' | string;
  children?: NavigationItem[];
}
