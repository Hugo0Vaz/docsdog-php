{
  description = "Docs Dog — Opinionated project documentation tool";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    flake-parts.url = "github:hercules-ci/flake-parts";
  };

  outputs = inputs @ { self, nixpkgs, flake-parts, ... }:
    flake-parts.lib.mkFlake { inherit inputs; } {
      systems = [
        "x86_64-linux"
        "aarch64-linux"
        "x86_64-darwin"
        "aarch64-darwin"
      ];

      perSystem = { pkgs, ... }:
        let
          phpBase = pkgs.php;
          php = phpBase.buildEnv {
            extensions = { enabled, all }:
              enabled ++ (with all; [
                mbstring
                xml
                dom
                curl
                zip
                openssl
                tokenizer
                fileinfo
                pdo
                intl
              ]);
            extraConfig = ''
              memory_limit = 512M
            '';
          };
        in
        {
          devShells.default = pkgs.mkShell {
            name = "docsdog-php";

            buildInputs = [
              php
              phpBase.packages.composer
            ];

            shellHook = ''
              echo "🐕   Docs Dog — PHP Development Environment"
              echo "      PHP:      $(php --version | head -1)"
              echo "      Composer: $(composer --version 2>/dev/null | head -1)"
              echo ""
            '';
          };
        };
    };
}
